<?php
/**
 * PHP 8.1, Laravel 10
 * Datasource is a custom query builder created 4 years ago. 
 * A couple months I developed a new feature to include other datasources as subqueries.
 * Below are only the parts related to that new feature.
 */


namespace App\Infrastructure\DataSource;


class DataSource implements IDataSource
{
    protected Collection $fields;

    private ?object $definition = null;

    private ?WidgetDatasource $widget_datasource = null;
    
    /**
     * Used in `join` combined datasource to reference the joined tables.
     */
    private array $joined_aliases = [];

    //...

    public function __construct(protected TicketType $ticket_type, protected bool $soft_deleted = false)
    {
        throw_unless(UserContext::getUser(), BusinessLogicException::class, 'Datasource needs a UserContext::getUser() other than null');
        throw_unless(UserContext::getTicketType(), BusinessLogicException::class, 'Datasource needs a UserContext::getTicketType() other than null');

        $this->meta_model     = new MetaModel($ticket_type);
        $this->fields         = collect();
        $this->select_fields  = collect();
        $this->where_fields   = collect();
        $this->tables         = collect();

        $this->createFields();
    }

    protected function createFields(): void
    {
        //...
    }

    private function isBPMN(): bool
    {
        return $this->ticket_type->isBpmn();
    }

    protected function createStaticFields(): void
    {
        //...
    }

    protected function createDynamicFields(): void
    {
        //...
    }

    /**
     * Whether this datasource is a combination of other datasources.
     * @return bool False when it is a simple/common datasource originated from materialized ticket.
     */
    public function isCombined(): bool
    {
        return filled($this->definition('combined'));
    }

    public function getData($params = [], $skip_paging = false, $skip_count = false, $raw_format = true, $user_context = null): array
    {
        //Pass runtime_params e.g. ticket_id=1 to runtime_params
        $this->setRuntimeParams($params['params'] ?? []);
        $this->preGetData($params);

        $query = $this->getQuery($user_context);
        $kendoDatasource = new KendoDataSource($params, $this->fields);
        
        $kendoDatasource->applyFiltering($query);
        if (!$this->isCombined()) {
            (new TicketService())->applyFilterByUser($query, $user_context);
        }

        //...

        return ['total' => $count, 'items' => $tickets];
    }

    /**
     * @return QueryBuilder|EloquentBuilder EloquentBuilder when is a simple datasource, QueryBuilder when combined.
     */
    public function getQuery($user_context = null): QueryBuilder
    {
        if ($this->isCombined()) {
            $query = match ($this->definition('combined.type')) {
                'union' => $this->getCombinedQueryForUnion(),
                'join' => $this->getCombinedQueryForJoin(),
            };
        } else { // Else make simple-common query for tickets.
            //...
        }
        
        //...

        return $query;
    }

    private function getQueryForDependency(WidgetDatasource|int $widget_datasource): QueryBuilder
    {
        $widget_datasource = $widget_datasource instanceof WidgetDatasource
            ? $widget_datasource
            : WidgetDatasource::findOrFail($widget_datasource);

        return UserContext::isolate(UserContext::getUser(), $widget_datasource->ticketType, function () use ($widget_datasource) {
            $data_source = new DataSource($widget_datasource->ticketType);
            $data_source->loadFromJson($widget_datasource);
            $query = $data_source->getQuery();

            if (!$data_source->isCombined()) {
                app(TicketService::class)->applyFilterByUser($query);
            }

            return $query;
        });
    }

    private function wrapQuery(QueryBuilder $query, string $as, ?array $select_columns = []): QueryBuilder
    {
        return DB::query()->fromSub($query, $as)
            ->when($select_columns, fn(QueryBuilder $q) => $q->select(array_map(fn($c) => "$as.$c", $select_columns)));
    }

    private function getCombinedQueryForUnion(): QueryBuilder
    {
        $query = collect($this->definition('datasources'))
            ->reduce(function (?QueryBuilder $union_query, \stdClass $definition, $i) {
                $dependent_query = $this->getQueryForDependency($definition->id);
                if (!empty($definition->fields)) {
                    $as              = $this->widget_datasource?->id . "_dependent_ds_{$i}_" . $definition->id;
                    $dependent_query = $this->wrapQuery($dependent_query, $as, $definition->fields);
                }

                if (empty($union_query)) { // Only for first item
                    return $dependent_query;
                }

                /** @noinspection PhpParamsInspection */
                return $union_query->union($dependent_query, $definition->all ?? false);
            });

        if ($this->select_fields->isNotEmpty() || $this->where_fields->isNotEmpty()) {
            $query = $this->wrapQuery($query, "union_ds_{$this->widget_datasource?->id}");
        }

        return $query;
    }

    private function getCombinedQueryForJoin(): QueryBuilder
    {
        return collect($this->definition('datasources'))
            ->reduce(function (?QueryBuilder $query, \stdClass $join, $i) {
                $this->joined_aliases[$i] = $as = $this->widget_datasource?->id . "_joined_{$i}_" . $join->id;

                if (empty($query)) { // Only for first item
                    return $this->wrapQuery($this->getQueryForDependency($join->id), $as);
                }

                [$second_alias, $second] = explode('.', $join->second); // e.g. `0.ticket_id`, `1.ticket_type_id`

                return $query->joinSub(
                    $this->getQueryForDependency($join->id),
                    $as,
                    "$as." . $join->first,
                    $join->operator ?? '=',
                    $this->joined_aliases[$second_alias] . '.' . $second,
                    $join->type ?? 'left'
                );
            });
    }

    public function export($params, $filename, $format = 'xls', $chunkSize = 100)
    {
        //...
    }

    public function loadFromJson($json): void
    {
        //...
    }

    private function addDirectFieldForCombined(\stdClass $field): DataSourceField
    {
        $datasource_field = new DataSourceField(array_only((array)$field, [
                'db_table',
                'db_column',
                'db_expression',
                'name',
                'data_type',
                'friendly_name',
            ]) + ['type' => DataSourceField::CALCULATED]
        );
        $this->addField($datasource_field);
        $datasource_field->filter_props->control_type = $field->filter_props->control_type;

        return $datasource_field;
    }

    protected function definition(?string $key = null): mixed
    {
        return data_get($this->definition, $key);
    }

    //...
}
