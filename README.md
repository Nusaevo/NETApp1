<h1>Project Documentation</h1>
<hr>

<h2>Table of Contents</h2>
<ul>
    <li><a href="#how-to-run-the-project">How to Run the Project</a></li>
    <li><a href="#github-workflow">GitHub Workflow</a></li>
    <li><a href="#livewire-concepts">Livewire 3 Concepts</a></li>
</ul>

<hr>
<h2 id="how-to-run-the-project">How to Run the Project</h2>
<hr>
<ol>
    <li><strong>Clone the Repository</strong> (if you haven't already):
        <pre><code>git clone https://github.com/andrych17/NETApp1.git cd NETApp1</code></pre>
    </li>
    <li><strong>Install Node.js Dependencies</strong>:
        <pre><code>npm install npm run dev</code></pre>
    </li>
    <li><strong>Install PHP Dependencies</strong>:
        <pre><code>composer install</code></pre>
    </li>
    <li><strong>Set Up the Environment</strong>: 
        <p>Copy the <code>.env.example</code> file to create your own <code>.env</code> file:</p>
        <pre><code>cp .env.example .env</code></pre>
        <p>Then, generate an application key:</p>
        <pre><code>php artisan key:generate</code></pre>
    </li>
    <li><strong>Run the Application</strong>:
        <p>Start the Laravel development server:</p>
        <pre><code>php artisan serve</code></pre>
    </li>
    <li><strong>Access the Application</strong>:
        <p>Open your web browser and go to <a href="http://localhost:8000">http://localhost:8000</a> to see the application in action.</p>
    </li>
</ol>

<hr>
<h2 id="github-workflow">GitHub Workflow</h2>
<hr>
<p>To collaborate on this project using Git and GitHub, please follow the steps outlined below.</p>
<h3>Branch Naming Convention</h3>
<p>All development work should be done on a feature branch. Use the following naming convention for your branches:</p>
<pre><code>dev-yourname-#issue-number-description</code></pre>

<p><strong>Examples:</strong></p>
<ul>
    <li><code>dev-andry-#45-fix-login-bug</code></li>
    <li><code>dev-alex-#23-add-user-profile-module</code></li>
</ul>

<h3>Clone the Repository</h3>
<p>Start by cloning the repository to your local machine:</p>
<pre><code>git clone https://github.com/andrych17/NETApp1.git cd NETApp1</code></pre>

<h3>Switch to the Staging Branch</h3>
<p>Check out the <code>staging</code> branch and ensure it's up to date:</p>
<pre><code>git checkout staging git pull origin staging</code></pre>

<h3>Create a New Feature Branch</h3>
<p>Create a new branch from the <code>staging</code> branch using the naming convention:</p>
<pre><code>git checkout -b dev-yourname-#issue-number-description</code></pre>

<h3>Make Your Changes</h3>
<p>Now you're ready to make changes to the codebase. Implement your feature or bug fix as required.</p>

<h3>Commit Your Changes</h3>
<p>After making changes, add and commit them to your branch:</p>
<pre><code>git add . git commit -m "A brief description of the changes made"</code></pre>

<h3>Push Your Branch to GitHub</h3>
<p>Push your feature branch to the remote repository:</p>
<pre><code>git push origin dev-yourname-#issue-number-description</code></pre>

<h3>Create a Pull Request</h3>
<p>Once your branch is pushed to GitHub, create a Pull Request (PR) to merge your changes into the <code>staging</code> branch:</p>
<ol>
    <li>Navigate to the repository on GitHub.</li>
    <li>Click on the "Compare &amp; pull request" button next to your recently pushed branch.</li>
    <li>Ensure the base branch is set to <code>staging</code> and the compare branch is your feature branch.</li>
    <li>Provide a clear title and description for your PR, referencing the issue number if applicable.</li>
    <li>Submit the Pull Request.</li>
</ol>

<hr>
<h2 id="livewire-concepts">Livewire 3 Concepts</h2>
<hr>
<h3>Models</h3>
<p>In Livewire 3, models are typically used to represent the data you are working with. For this project, the models can be found in <code>app/models/appcode</code>. You can bind your Livewire components directly to Eloquent models, allowing for seamless data manipulation and retrieval. When you update the model in the component, it automatically reflects in the database.</p>

<h3>Livewire Components</h3>
<p>Livewire components are the building blocks of your application. Each component can contain its own logic, rendering, and lifecycle hooks. For this project, Livewire components are stored in <code>app/Livewire/appcode</code>. You can create components for different parts of your application, allowing you to organize your code better. Components are written in PHP and can be rendered in Blade views, located in <code>resources/Livewire/appcode</code>.</p>

<h3>Resource Blade Views</h3>
<p>Resource Blade views are used to display and manage your resources (like models). They are integrated with Livewire components to provide a reactive user interface. You can define views for creating, updating, and displaying your models, making it easier to manage CRUD operations without writing much JavaScript. These views will use lowercase letters and dashes in their naming conventions, reflecting the structure of the Livewire components.</p>

<h3>Services</h3>
<p>For service methods, you can create a service class in <code>app/Services/appcode</code> to store various methods that can be called from your application configuration, such as <code>SysConfig1</code>, according to the app code.</p>
