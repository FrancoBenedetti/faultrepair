---

# **Project Brief: Equipment Fault Reporting Application**

## **1. Application Overview**

Develop a web-based equipment fault reporting application for small businesses and home owners with appliances and catering equipment. The application will facilitate the reporting of faults, assignment of jobs to service providers, and tracking of repairs through a defined workflow. The system must support multiple independent clients and service providers, each with their own internal user management. The system must ensure strict data segregation and access control based on user roles.

## **2. Technical Stack**

* **Backend:** PHP (Vanilla, no framework)
* **Database:** MariaDB
* **Frontend:** Vue.js (SPA)
* **API:** RESTful API to connect the Vue.js frontend to the PHP backend.
* **Deployment Environment:** Standard LAMP stack.

## **3. User Roles and Permissions**

The system must support the following user roles with clearly defined permissions.

* **Client Side:**
  * **Reporting Employee:**
    * Can create and submit a new fault report.
    * ~~Can view all unarchived jobs for their 'location' (their business/site).~~
    * Cannot select a service provider for assignment.
    * Can view which service provider a job has been assigned to.
    * Can view their own submitted reports in detail.
    * Can edit a report only up until it is assigned to a service provider.
    * *Hidden from view and not accessible*:
      * Progress bar 
      * User management section
      * Job management header
      * Jobs recorded by other reporting employees
      * Locations section
      * Approved service providers*
  * **Site Budget Controller:**
    * Has all the permissions of a Reporting Employee.
    * **Acts as the initial user for a new business.**
    * Can add, edit, and remove Reporting Employees.
    * Can assign the **Site Budget Controller** role to another employee.
    * Can view a dashboard of users and see reports made by each user.
    * Can re-assign a job to a different service provider if the current one rejects it.
    * Can approve and confirm the completion of a job.
    * Can select a subset of 'approved service providers' from the list of all service providers.
* **Service Provider Side:**
  * **Service Provider Admin:**
    * Can manage users (Technicians) for their service provider company.
    * Has a dashboard to view all jobs assigned to their company.
    * Can assign jobs to a specific Technician within their company.
    * Can set job statuses as:
      * "In Progress" 
      * "Quote Provided" 
      * "Repaired"
      * "Payment Requested"
      * "Completed"
  * **Technician:**
    * Can view jobs that have been assigned to the user only
    * Can add notes to a job report (Technician Notes)
    * Can change a job's status from "In Progress" to 
        "Repaired"
        "Not repairable"

## **4. Workflow and Job Statuses**

The application must manage the state of a job through the following workflow. The system should also store the date and the user who made each status change.

* **Client-side Status Changes (set by client):**
  * `Reported`
  * `Assigned`
  * `Quote Requested`
  * `Quote Accepted`
  * `Completion Confirmed`
* **Service Provider-side Status Changes (set by service provider):**
  * `Declined` - This is enabled when the status of the job is 'Assigned' and is only settable by the service provider administrator. 
  * `In Progress` - This setting is enabled when the status of the task is 'assigned' and is automatically applied when a technician is assigned to the job. This is used to inform the client that the job has been accepted. This is only settable by the administrator
  * `Quote Provided` - This is applied only when a quotation was requested by the client and has been submitted by the service provider via another channel, e.g. via email
  * `Repaired` - This is enabled only when the status is 'In Progress' and is settable by either the administrator or the technician
  * `Not repairable` - This is enabled only when the status is 'In Progress' and is settable by either the administrator or the technician 
  * `Payment Requested` - This is only settable when the status is 'Repaired'. This can only be set by the administrator.
* **Final Archiving Flags:**
  * `Archived by Service Provider` (hides the job from the service provider's active job list)
  * `Archived by Client` (hides the job from the client's active job list)

## **5. Database Schema (MariaDB)**

The database must be designed to store the following entities. The agent must create a robust schema with appropriate primary and foreign keys.

* `clients` (to store business/client information)
* `service_providers` (to store service provider business information)
* `users` (to store user credentials and map to `clients` or `service_providers`)
* `roles` (to manage user permissions)
* `jobs` (the core table for fault reports)
* `job_status_history` (to log all status changes)
* `client_approved_providers` (a linking table for a client's selected service providers)
* `job_notes` (for technician notes)

The `jobs` table must include the following fields:

* `client_location` (ID linked to a location table)
* `item_identifier` (e.g., serial number, asset tag)
* `fault_description`
* `technician_notes` (a long text field)
* `assigned_provider_id`
* `reporting_user_id`
* `assigning_user_id`
* `contact_person`
* `assigned_technician_id`
* `job_status`
* `archived_by_client` (boolean flag)
* `archived_by_service_provider` (boolean flag)

## **6. Application Functionality**

* **User Registration:**
  * A public self-service form for new client businesses. The first user to register for a business is automatically a **Site Budget Controller**.
  * A public self-service form for new service provider businesses. The first user to register for a service provider is a **Service Provider Admin**.
* **User Management:**
  * Site Budget Controllers and Service Provider Admins must have dashboards to manage their respective users.
  * The system must enforce that a user is only associated with one business (either a client or a service provider).
* **Dashboard Views:**
  * **Mobile-Optimized Dashboard:** A simplified view for technicians, optimized for quick actions like viewing job details and changing status to "Repaired."
  * **Desktop Dashboard:** A comprehensive view for Site Budget Controllers and Service Provider Admins to manage users and view detailed reports. This dashboard should allow users to click on an entry to see full details.
* **Security:**
  * The backend PHP must implement a secure RESTful API using authentication tokens (e.g., JWT) to protect all endpoints.
  * All user input must be sanitized to prevent SQL injection and other vulnerabilities.
  * Permissions must be checked on every API call to ensure a user can only access information and perform actions entitled to their role.
* **Reporting:**
  * The system must provide views for users to see reports applicable to them, including:
    * All jobs for their site/business.
    * Reports they have personally submitted.
    * Who reported a job, the current status, and history of status changes.
* **API Endpoints:**
  * Create a well-documented set of API endpoints for all CRUD operations related to users, jobs, and status changes.
  * Endpoints should be designed to handle data based on the user's role and associated business.

## **7. Key Constraints**

* **Data Segregation:** It must be impossible for a user from one business (client or service provider) to access information belonging to another business unless explicitly assigned a job.
* **Mobile vs. Desktop:** The user registration/management and detailed reports can be limited to the desktop environment. The core functionality of reporting a fault and technicians marking a job as complete must be fully functional and optimized for mobile devices.
* **No Frameworks:** All backend code must be written in vanilla PHP.
* **Front-end Framework:** All front-end code must be written in Vue.js.
