IxDF Interview
==============

Answered by [**Theofanis Vardatsikos**](https://www.linkedin.com/in/theofanis-vardatsikos/) (vardtheo@gmail.com)

### 1. Please describe an interest, favorite activity, or hobby of yours. It could be anything: just something you enjoy doing. For this particular answer, please only write up to 60 words and do it in a way that captivates your readers (us!).  
 
1. I like finding solutions using my skills for ordinary or extraordinary problems of mine or of my closest friends, either by creating cloud systems or mobile apps. 
2. A special sub-category of this is creating new automations for my smart home or contributing to projects for smart home.
3. Exploring nature with my wife.

### 2. What is most important to you when you look for a new job? Please be completely honest and transparent about your situation and season of life; we’re not looking for “the right answer”. The more you tell us, the better we can align.  

Since meeting the lovely people in my current workplace I find this attribute to be one of the most significant for well-being and productivity. I believe a friendly environment enables people to work together and achieve more. Discussing tasks with cordial colleagues produces more refined and impactful results.


### 3. Everyone learns differently. How do you learn best? And what’s something new that you’ve learned in the past few months? Big or small, we’re curious!  

For completely new specialties I learn by example, e.g. on my early years as Laravel developer I was looking through the implementation of the framework itself. After having the basic knowledge then in each time I use my skills I see something that could be performed better and thus the experience builds up. This is why I am confident I am a Laravel expert and solution finder. 

The last remarkable information I learned was several months ago. It was an alternative method to structure the SQL queries in way which takes significantly less time to filter through large datasets.

Another adjustment is, I have setup an educational meeting with my team members to share new ways of developing features, which is showing promising results.


### 4. How did you hear about this open position?

I was informed by browsing larajobs.com


### 5. Show us some examples of concrete work you’re proud of having done yourself. Please tell us why you are proud of what you sent us. Please be as specific as possible.

Well, that's an intriguing question. While I've had numerous moments of pride, none of them stand out permanently. I often find myself feeling proud when I develop new features without "polluting" the existing codebase. This involves injecting extra functionality into targeted points in the code, thereby modifying targeted procedures without disrupting the overall system. In other words, you achieve more with less, which I find to be a significant accomplishment. Even when developing larger features, I prefer to package the code neatly within dedicated folders containing all the related business logic, and then seamlessly integrating it with just a single line of code in the main system. This approach is one of the reasons I admire Laravel, its out-of-the-box features provide wide range of hooks to bind extra business logic seamlessly. These practices contribute to minimizing technical debt, so we can keep being productive at the same rate in future.  

> A technical example for this is when I had to integrate notifying the greek IRS in real time about the issuance of each invoice through a certified provider. Each provider used different protocol and data format (restful, xml, openId, sftp, etc). The implementation for each provider is placed in a separate package. Each "connector" is implementing the same "contract"/"interface" and binding itself to the [container](https://laravel.com/docs/master/container). The [ServiceProvider](https://laravel.com/docs/master/providers) for each connector was registering a Listener listening when an Invoice is being created and while capturing that event it transmitted that data to the external service and got back the QR codes needed to print onto the invoice. With this method I was able to support a new provider within one or two days without changing anything on the main codebase. You can find the implementation of one provider in [snippets](./snippets/OxygenOnline/).

> An additional technical achievement was when I created a cloud server to observe other servers on customers' premise. On each on-premise server I setup ssh tunnels to that cloud server. There I placed a Laravel project to access all the other server and gather info, viability stats, etc. Furthermore, that Laravel project had an admin panel from which we could add/remove IPs from the Ubuntu firewall UFW, that allowed us to access the internal on-prem servers like cloud servers without using remote desktop softwares.

If necessary, I'm more than willing to elaborate into specific use cases of my work and even demonstrate parts of code during a video call.


### 6. Can you give a concrete example of a recent situation where you “took ownership” of a task/project/etc at work and made something truly positive happen? How do you define “taking ownership” of your work?

On our current platform, we have a feature that displays the number of tickets per phase. About a month ago, a customer needed to incorporate filters into counting. It was evident from the start that implementing these filters would definitely lead to noticeable delays in response time. Although this aspect wasn't the primary concern of the project manager, I deemed it inappropriate to deploy a feature that could potentially disrupt user operations. Therefore, I requested additional time from the project manager to mitigate this issue. This allowed me to refactor every component related to the procedure, ultimately enhancing its performance and addressing the initial concern before it was launched.

Another instance occurred last year when we faced a blocking error with our [BPMN](https://en.wikipedia.org/wiki/Business_Process_Model_and_Notation) service (node.js). For the following 4 days, including the weekend, every developer and technical manager was tasked to find the problem and fix it. Eventually, no one found the problem. On the fourth day I took the initiative to rebuild the BPMN service from scratch using Laravel to replace the existing one. This experience demonstrates my commitment to finding innovative solutions, no matter the challenge. 

In my previous job, I engaged directly with customers' employees to gather requirements for new features. To maximize the impact of the feature, I proactively communicated with employees from multiple customers, so that a broader audience would benefit. If it necessary I can elaborate more during a call.

I would define ownership as taking care of the full cycle of a feature like a small scale product owner. Learn the clients' needs, finding a solution, designing it, implementing it and finally observe how it impacts the users. This includes thinking out-of-the-box and optimizing in advance.


### 7. What are the key skills you (would) bring to an asynchronous and remote work environment? What key skills do you feel you need to improve for you to fully thrive in such an environment?

I possess five years of proven remote work experience, demonstrating my ability to excel independently while ensuring seamless collaboration and productivity.
I've learned the importance of proactive communication, updating my team for unexpected challenges. I am strong advocate of code reviews on pull-requests to practically show code alternatives.


### 8. Which season of life are you currently in and what are your career goals? For example, are you in a season of life where you work long hours to learn as much as possible? Or in a season where you prioritize work-life balance because you’ve already gained substantial experience? The more transparency you give us, the better we can align and create common goals for your career.  

I have mastered the skills related to this role, so for me it is an ordinary task to design and implement new platforms using Laravel ecosystem. So I am looking to balance my work-life while providing high-impact products. In the meantime new technologies emerge or I learn even better methods to utilize per case. Learning is a never ending process!


### 9. What is your approach to monitoring the performance of live web applications? What techniques do you use to maintain good performance levels?

Primarily, I utilize Laravel Telescope. However, since it can generate a large database which results in significant costs and may delay main operations, I write custom code to selectively log only the cases I consider critical for debugging, assessment, or optimization. Additionally, I add extra code to attach tags to each occurrence to make the searching per-case easier. By the way, I look forward to use the new [Pulse](https://pulse.laravel.com) package.

Secondly, as I gain more familiarity with the product and identify potential problem areas, I introduce specific logging under conditions that I find suspicious.
Laravel Horizon is an outstanding tool not only for managing background jobs but also for logging these jobs along with their errors. It provides detailed statistics and history for each type of job separately.

On extremely rare and critical occasions that are considered urgent, I add code to send log messages directly to a Slack channel for immediate action.

At times, these methods may be complemented with hotjar.com to capture user actions on the front end, providing us the same experience as the user and potentially finding alternative solutions. However, I find this method can be very intrusive for the user.

Furthermore, depending on our specific goals, there are methods that are more performant or cost-effective. For instance, to determine the rate of HTTP calls per minute/hour instead of using Telescope, an application firewall with external logging to Elastic is more appropriate.


### 10. Could you please send us a sample of some production code you have written and are particularly proud of or find intriguing? Please also explain why you are proud of this code or why you find it interesting. I understand that you are likely under an NDA for most of your code, so it doesn't need to be executable - just a few snippets will suffice.


Below are public repositories from previous assessment projects:

- [theofanisv/travels-assessment](https://github.com/theofanisv/travels-assessment)
- [theofanisv/weather-data-collector](https://github.com/theofanisv/weather-data-collector)


[Mini-apps](https://bitbucket.org/theofanis_/mini-apps/src/master/) is a hobby project for helping my friends with their daily tasks, unfortunately it is only available in greek, but have a look [mini-apps.theograms.tech](https://mini-apps.theograms.tech).

The folder [snippets](./snippets) in this repository contains some classes with production code.

------
*Your questions intrigued me. 💛 I hope my answers intrigue you as well❗️*
