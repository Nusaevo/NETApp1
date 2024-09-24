Documentation :
https://livewire.laravel.com/docs/quickstart

How to Run the Project
To set up and run the Laravel project, follow these steps:

Clone the Repository (if you haven't already):

bash
Copy code
git clone <repository-url>
cd <project-directory>
Install Node.js Dependencies: Run the following commands to install the front-end theme:

bash
Copy code
npm install
npm run dev
Install PHP Dependencies: Use Composer to install the PHP dependencies:

bash
Copy code
composer install
Set Up the Environment: Copy the .env.example file to create your own .env file:

bash
Copy code
cp .env.example .env
Then, generate an application key:

bash
Copy code
php artisan key:generate
Run the Application: Start the Laravel development server:

bash
Copy code
php artisan serve
Access the Application: Open your web browser and go to http://localhost:8000 to see the application in action.
