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

## Livewire 3 Concepts

### Models

In Livewire 3, models are typically used to represent the data you are working with. For this project, the models can be found in `app/models/appcode`. You can bind your Livewire components directly to Eloquent models, allowing for seamless data manipulation and retrieval. When you update the model in the component, it automatically reflects in the database.

### Livewire Components

Livewire components are the building blocks of your application. Each component can contain its own logic, rendering, and lifecycle hooks. For this project, Livewire components are stored in `app/Livewire/appcode`. You can create components for different parts of your application, allowing you to organize your code better. Components are written in PHP and can be rendered in Blade views, located in `resources/Livewire/appcode`.

### Resource Blade Views

Resource Blade views are used to display and manage your resources (like models). They are integrated with Livewire components to provide a reactive user interface. You can define views for creating, updating, and displaying your models, making it easier to manage CRUD operations without writing much JavaScript. These views will use lowercase letters and dashes in their naming conventions, reflecting the structure of the Livewire components.

### Services

For service methods, you can create a service class in `app/Services/appcode` to store various methods that can be called from your application configuration, such as `SysConfig1`, according to the app code.

