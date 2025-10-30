# Job Workflow Documentation

## Overview

The correct name for job here is 'job ticket'. We will use 'job' for brevity.

A job is always created by a client user or reporter or by a client budget or department controller. Inherently therefore, when a job is in the 'Reported' state, all the fields that were available for data capture at the time of creation, remain available for editing to both roles 1 and 2 (see below).
In the reported state, only Role 2 may change the state of a job and then only to the states allowed.

The state of the job determines:

* The role that is able to change the state
* The possible next states
* The fields that are available for editing and viewing
* In certain cases, some fields are required before a state can be selected

By changing its state, a user moves the job away from themselves and to the next entity and user in the chain

## Job Types

### Jobs for subscribed service providers (type S)

These service providers receive the jobs and interact with the Snappy system using the frontend service provider dashboard. The role changes are as defined by the Status System defined below

### Jobs for non-subscribed service providers (type XS)

These service providers have independent job management using their own system or have a manual system. For these jobs, the state is changed by the Client Role 2 user.

## User Roles

### Client/Asset owner

1. Reporter/reporting employee/client user
2. Client admin/Controller/budget controller
   * For platform (type S) providers, view only stages are: Assigned, In Progress, Incomplete, Confirmed, Rejected, Quote Requested,  Unable to Quote,  Job Cancelled 

### Service Provider

3. Service provider admin/Dispatcher/Supervisor/Chief technician
4. Technician/Artisan/Tradesman/Service Provider User

## Status System (Complete Workflow)

### Status Flow

1. **Reported** - Job is created by Client (role 1 or role 2):
   
   - Permissions
     - Role 1: Edit job content
     - Role 2: Edit job content, select Service Provider, Change State
   - Allowed next states:
     - `Rejected` - the request is terminated. No further action is needed. A reason must be provided
     - `Quote Requested` - A quote is required from the service provider
     - `Assigned` - the job is assigned to a provider to deliver a service directly
   - Rules:
     - A service provider selection is required before the `Quote Requested` or `Assigned` state can be selected. The default state set on service provider selection is `Assigned`
   - UI Implementation - Client Dashboard
     - User (role 2) selects a service provider (when applicable) using a drop down and/or then clicks a radio button with options to "Request a":
       - Service (backend sets state to `Assigned`)
       - Quote (backend sets state to `Quote Requested`)
       - Reject (backend saves state to `Rejected`)

2. **Assigned** - Job is available for editing by the service provider (role 3):
   
   - Permissions
     - Client Role 1 and Role 2 can view
     - Role 3 can change state and edit certain fields, such as instructions to technicians
     - Role 4 - no permissions
   - Allowed next states
     - `Declined` A reason must be provided (eg. 'No spares available, equipment is obsolete')
     - `In Progress` This can only be selected if a technician is allocated.
   - Rules:
     - A technician selection is required for the job to be moved into the `In Progress` state
     - **For XS (External Service Provider) jobs:**
       - Only Client Role 2 can change the state
       - Allowed next states: `Declined`, `In Progress`
       - No technician allocation required for `In Progress` state (internal to XS provider)
       - Role 2 can edit ALL fields and change ANY status for XS jobs
   - UI implementation - Service Provider Dashboard
     - User Role 3 selects a technician when applicable and/or clicks a radio button with options to:
       - Accept the job (must have a technician), setting it to the `In Progress` state
       - Decline the job (must have a reason), setting it to the `Declined` state
   - UI implementation - Client Dashboard (for XS providers)
     - Client Role 2 can transition XS jobs directly to `In Progress` or `Declined` without technician assignment

3. **In Progress** - Job is available to the allocated technician (Role 3 or 4) for work to proceed:
   
   - Permissions
     - Client Role 1 and 2 can view, but cannot edit.
     - Supervisor (role 3) or Technician (role 4) can add notes and update status
   - Allowed next states
     - `Completed` A note of explanation is optional
     - `Cannot repair` A reason must be provided
   - Subscription usage is tracked

4. **Completed** - Service Provider Role 4 and Role 3 (Technician) marks work as finished
   
   - Permissions
     - Client Role 1 and 2 can change state
     - Service Provider role 3 and 4 can view only
   - Allowed next states:
     - `Confirmed` An optional note can be provided
     - `Incomplete` A note is required, the user could upload images

5. **Incomplete** - Client representative (role 1 or role 2) rejects completion
   
   - Permissions
     - Client Role 1 and 2 can view only
     - Service provider role 3 can select a technician, include or modify instructions, and transition state
     - **For XS (External Service Provider) jobs:**
       - Client Role 2 can select a technician (optional for internal tracking), change status, and edit all job details
   - Allowed next states
     - `In Progress` A technician must be allocated. Typically there would already be one, but this can be changed
     - `Completed` A note of explanation is required, images could be added
     - **For XS jobs:** `In Progress` transition does NOT require technician assignment (handled internally by provider)
   - Rules:
     - This state behaves like 'Assigned' where technicians need to be assigned before work can restart
     - XS providers manage technician assignment internally, so Role 2 can transition without assignment

6. **Cannot repair** - Service Provider Role 4 and Role 3 (Technician) marks work as unrepairable.
   
   - Permissions
     - Service provider (role 3 and 4) view only
     - Client role 1 (view only)
     - Client role 2 can change state and edit
   - Allowed next states:
     - `Confirmed` terminate the job
     - `Incomplete` ask service provider to review this assessment
     - `Assigned` reassign to a different service provider (client selects new provider)

7. **Confirmed** - Client representative (role 1 or 2) accepts that the job is completed. This is a terminal state.
   
   - No further state changes are possible
   - The job can be archived respectively by Client Role 2 or Service Provider Role 3
   - The job can be viewed by any of the roles

8. **Declined** - The Service provider (role 3) has declined the job
   
   - Permissions
     - Service Provider (role 3 and 4) can view the job while allocated to the service provider
     - Client Role 1 can view
     - Client Role 2 can reallocate the job to another service provider
   - A job in this state must be treated in the same way as the reported state. The difference is that the notes or comments by service providers must be deleted if the job is reassigned to a different service provider
   - Allowed next states
     - `Rejected`
     - `Quote Requested`
     - `Assigned`

9. **Rejected** - This is a terminal state. The service request is not accepted by the Client admin and no further action is needed

10. **Quote Requested** - The Client has requested a quote
    
    - Permissions
      - Client (view only)
      - Service Provider (role 3) can change state
    - Allowed next states
      - `Quote Provided` The quote must be created before it can be provided. Use a separate quote view to create quotes and record these. As it is created, it should be automatically linked to this job.
      - `Unable to quote` A reason should be provided

11. **Quote Provided** - Service Provider has submitted a quotation
    
    - Permissions
      - Client (role 2) can change state and view quote
      - Client (role 1) can view job but cannot view quote
      - Service Provider (role 3) can view job and quote
    - UI Implementation - Client Dashboard
      - Client role 2 views the quote in QuotationDetailsModal with 3 radio button options:
        - Accept Quote: Changes job state to `Assigned` and quote to accepted
        - Reject Quote: Changes job state to `Rejected`, quote to rejected, clears quotation reference
        - Request New Quote: Changes quote status to expired, allowing service provider to submit revised quote with optional client notes
    - Allowed next states
      - `Assigned` The quote is accepted and the service provider can continue with the job
      - `Rejected` Terminate this job with no further action
      - `Quote Provided` (stays) - Service provider can submit revised quote when quote status is expired
      - Re-assign to another service provider by resetting the quote information (future implementation)

12. **Unable to Quote** - Service Provider cannot provide quotation
    
    - Permissions
      - Service Provider (role 3) can change state
      - Client (role 1 and 2) can view
    - Allowed next states
      - `Reported` - Return to reported state for reassignment to different provider
      - `Rejected` - Terminate the job entirely

13. **Quote Rejected** - Client rejects the service provider's quotation
    
    - Permissions
      - Client (role 2) can change state
      - Service Provider (role 3) can view
    - Allowed next states
      - `Quote Requested` - Request revised quote from same provider
      - `Reported` - Reassign to different provider

14. **Quote Expired** - Quote response time limit exceeded without client action
    
    - Permissions
      - System automatically manages this state
      - Client (role 2) can change state
    - Allowed next states
      - `Quote Requested` - Extend deadline and request quote again
      - `Reported` - Reassign to different provider

15. **Job Cancelled** - Job cancelled by client at any point before completion
    
    - Permissions
      - Client (role 2) can cancel any active job
      - Service Provider can view cancelled jobs
    - Allowed next states
      - None - This is a terminal state
