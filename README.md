## How to?

OEE Management System
This project is an OEE (Overall Equipment Effectiveness) Management System built using Laravel, PostgreSQL, and Python. The system tracks production data and downtime to calculate OEE metrics.

Prerequisites
Make sure you have the following installed on your machine:

- Node.js and npm
- PHP and Composer
- PostgreSQL and pgAdmin
- Python

Getting Started
Follow these steps to set up and run the project:

1. Clone the repository
   git clone this repository
   cd oee-management-system
2. Install npm dependencies
   " npm install "
3. Run database migrations
   " php artisan migrate:fresh "
4. Open pgAdmin
   Set up your PostgreSQL database and make sure it's running. Update your .env file with the correct database connection details.
5. Serve the Laravel application   
   " php artisan serve "
6. Configure production and downtime data files
    Open the script.py and script-downtime.py files and set the dates and times according to the current date and the desired times.
7. Run the process script
    Open a terminal and navigate to the project directory, then run: " python process.py "
8. Refresh your browser
Open your browser and navigate to the Laravel server URL (usually http://127.0.0.1:8000). Refresh the page to see the updated data.

Additional Information
Ensure that your Python scripts (data_produksi.py and downtime.py) are correctly formatted and contain the necessary data before running the process.py script.
If you encounter any issues, check the Laravel logs (storage/logs/laravel.log) and PostgreSQL logs for more information.
