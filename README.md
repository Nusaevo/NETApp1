Documentation :
https://livewire.laravel.com/docs/quickstart
## How to Run the Project

To set up and run the Laravel project, follow these steps:

<ol>
    <li>
        <strong>Clone the Repository</strong> (if you haven't already):
        <pre><code>git clone &lt;repository-url&gt;
cd &lt;project-directory&gt;</code></pre>
    </li>
    <li>
        <strong>Install Node.js Dependencies</strong>:
        <pre><code>npm install
npm run dev</code></pre>
    </li>
    <li>
        <strong>Install PHP Dependencies</strong>:
        <pre><code>composer install</code></pre>
    </li>
    <li>
        <strong>Set Up the Environment</strong>:
        Copy the <code>.env.example</code> file to create your own <code>.env</code> file:
        <pre><code>cp .env.example .env</code></pre>
        Then, generate an application key:
        <pre><code>php artisan key:generate</code></pre>
    </li>
    <li>
        <strong>Run the Application</strong>:
        Start the Laravel development server:
        <pre><code>php artisan serve</code></pre>
    </li>
    <li>
        <strong>Access the Application</strong>:
        Open your web browser and go to <a href="http://localhost:8000">http://localhost:8000</a> to see the application in action.
    </li>
</ol>
Access the Application: Open your web browser and go to http://localhost:8000 to see the application in action.
