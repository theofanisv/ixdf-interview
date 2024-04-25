<?php
/**
 * PHP 7.4, Laravel 8.0
 * This is the connector for transimmiting the issued invoices to the greek IRS in real-time and receives the authorized qr code.
 */

namespace App\Packages\OxygenOnline;

use App\Models\Invoice;
use App\Packages\Aade\Contracts\AadeInvoiceMarker;
use App\Packages\Aade\Contracts\AadeInvoiceMarkerActions;
use App\Packages\OxygenOnline\Exceptions\OxygenOnlineException;
use App\Support\Vat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OxygenOnline implements AadeInvoiceMarker
{
    use AadeInvoiceMarkerActions {
        queryUnsentInvoices as private _queryUnsentInvoices;
    }

    public const PRODUCTION_URL = 'https://api.mydataprovider.gr/v1';
    public const SANDBOX_URL = 'https://sandbox-api.mydataprovider.gr/v1';

    public const EXTERNAL_VERIFICATION_URL = 'https://www.iview.gr/'; // with trailing '/'

    public const REQUEST_FAILED_CONNECTION_ERROR = 1;
    public const REQUEST_FAILED_MYDATA_TIMEOUT = 2;
    public const REQUEST_FAILED_VALIDATION_ERROR = 3;

    public const PROVIDER_MESSAGE = "Πάροχος Ηλ. Τιμολόγησης: Cloud Services Ι.Κ.Ε. - https://www.pelatologio.gr/";

    public static function baseUrl(): string
    {
        switch ($url = config('services.oxygen-online.base_url')) {
            case 'production':
                return self::PRODUCTION_URL;
            case 'sandbox':
                return self::SANDBOX_URL;
            default:
                throw_unless(is_url($url), OxygenOnlineException::class, "Invalid base url '$url'");
                return $url;
        }
    }

    public function queryUnsentInvoices(): Builder
    {
        return $this->_queryUnsentInvoices()
            ->when(config('services.oxygen-online.mark_after'), fn(Builder $q, $after) => $q->where('issued_at', '>', $after));
    }

    public function send(Invoice $invoice): Invoice
    {
        $this->approveSend($invoice);
        $data = $this->createData($invoice);

        try {
            $response = Http::oxygenOnline()->post('/invoices', $data)->throw();
        } catch (ConnectionException $e) {
            $this->handleConnectionException($e, $invoice);
            /** @noinspection PhpUnreachableStatementInspection */
            throw $e; // never reachable because handleConnectionException always throws exception
        } catch (RequestException $e) {
            $response = $this->handleRequestException($e, $invoice);
        }

        $response_data = $response->json() + [
            //...
            ];

        $invoice->pushToParameter('oxygen_online.history', $response_data);
        // Always fill the external verification link either on success or myData connectivity failure.
        $invoice->setParameters([
            'aade.external_verification_link' => array_get($response_data, 'iview_url') ?: $this->generateExternalVerificationLink($invoice),
            'aade.provider_name'              => self::PROVIDER_MESSAGE,
            'aade.marked_at'                  => $marked_at,
        ]);

        // If connection to oxygen was successful but failed from myData.
        // Then response is successful but does not contain mark, etc.
        if (empty($response_data['mark'])) {
            //...
            throw $this->exception("Did not receive mark while sending invoice {$invoice->debug_name}", $invoice->getParameter('aade.transmission_failure.code'))->withContext(['response' => $response_data]);
        }

        $this->logger()->info("Sent OxygenOnline Invoice {$invoice->debug_name}", $response_data);

        $invoice
            ->unsetParameter('aade.transmission_failure.message')
            ->setParameters([
                //...
            ])
            ->save();

        return $invoice;
    }

    /**
     * @throws OxygenOnlineException Always throws exception.
     */
    private function handleConnectionException(ConnectionException $e, Invoice $invoice): void
    {
        $invoice->setParameters([
            'aade.marked_at'                    => now()->toDateTimeString(),
            'aade.external_verification_link'   => $this->generateExternalVerificationLink($invoice),
            'aade.provider_name'                => self::PROVIDER_MESSAGE,
            'aade.transmission_failure.code'    => self::REQUEST_FAILED_CONNECTION_ERROR,
            'aade.transmission_failure.message' => __("aade.transmission_failure." . self::REQUEST_FAILED_CONNECTION_ERROR),
        ])->save();

        throw $this->exception("Connection error while sending Invoice {$invoice->debug_name}: {$e->getMessage()}", self::REQUEST_FAILED_CONNECTION_ERROR, $e);
    }

    private function handleRequestException(RequestException $request_exception, Invoice $invoice): Response
    {
        $e = $this->exception()::makeFromRequestException($request_exception);

        if ($e->invoiceGotMark()) {
            return $request_exception->response;
        }

        if ($e->isValidationError()) {
            //...

        } else if ($e->myDataServerError()) {
            //...

        } else if ($e->oxygenOutOfService()) {
            //...
        }

        throw $e;
    }

    public function createData(Invoice $i): array
    {
        $td = $i->tax_document;

        $data = [
            "number"               => $i->number,
            "series"               => blank($i->sequence) ? 0 : $i->sequence,
            "issue_date"           => $i->issued_at->format('Y-m-d'),
            "invoice_type"         => $td->getParameter('aade.invoice_type'),
            "document_id"          => (string)$i->id,
            "issuer_branch"        => config('parking.business.branch_code') ?: 0,
            "issuer_vat"           => config('parking.business.vin'),
            "items"                => $i->getInvoiceItems()->map(fn($item) => [
                "description"                    => $item->name,
                "net_amount"                     => n2d(Vat::removeFrom($item->charge)),
                "vat_amount"                     => n2d(Vat::getFrom($item->charge)),
                "vat_category"                   => $td->getParameter('aade.vat_category'),
                "vat_exemption_category"         => 0,
                "total_amount"                   => n2d($item->charge),
                "mydata_classification_category" => $td->getParameter('aade.mydata_classification_category'),
                "mydata_classification_type"     => $td->getParameter('aade.mydata_classification_type'),
            ]),
            "payment_method"       => $i->payment_method->getParameter('aade.code'),
            "payment_amount"       => abs($i->collected_amount_rounded),
            "transmission_failure" => $i->getParameter('aade.transmission_failure.code'),
        ];
        
        //...

        return $data;
    }

    public function generateExternalVerificationLink(Invoice $invoice): string
    {
        //...
    }

    public function name(): string
    {
        return 'Oxygen Online';
    }

    public function key(): string
    {
        return 'oxygen_online';
    }

    public function exception(...$parameters): OxygenOnlineException
    {
        return new OxygenOnlineException(...$parameters);
    }
}
