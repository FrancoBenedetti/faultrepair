# Job Workflow Documentation

## Job Types
### Jobs for subscribed service providers (type A)
These service providers receive the jobs and interact with the Snappy system using the frontend service provider dashboard. The role changes are as defined by the Status System defined below

### Jobs for non-subscribed service providers (type B)
These service providers have independent job management using their own system or have a manula system. For these jobs, the state is changed by the Client Role 2 user. 

## User Roles
### Client/Asset owner
   1. Reporter/reporting employee/client user
   2. Client admin/Controller/budget controller

### Service Provider
   3. Service provider admin/Dispatcher/Supervisor/Chief technician
   4. Technician/Artisan/Tradesman/Service Provider User

## Status System (Simplified)
### Status Flow 
1. **Reported** - Job is created by Client (role 1 or role 2):
   - Permissions
      - Role 1: Edit job content
      - Role 2: Edit job content, select Service Provider, Change State
   - Allowed next states:
      - `Rejected` - the request is terminated. No further action is needed. A reason must be provided
      - `Quote Requested` - A quote is required from the service provider
      - `Assigned` - the job is assigned to to a provider to delier a service directly
   - Rules:
      - A service provider selection is required before the `Quote Requested` or `Assigned` state can be selected. The default state set on service provider selection is `Assigned`
   - UI Implemntation - Client Dashboard
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
   - UI implementation - Service Provider Dashboard
      - User Role 3 selects a technician when applicable and/or clicks a radio button with options to:
         - Accept the job (must have a technician), setting it to the `In Progress` state
         - Decline the job (must have a reason), setting it to the `Declined` state

3. **In Progress** - Job is available to the allocated technician (Role 3 or 4) for work to proceed: 
   - Permissions
      - Client Role 1 an 2 can view, but cannot edit.
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
      - `Incomplete` A note is required, Theuser could upload images

5. **Incomplete** - Client representative (role 1 or role 2) rejects completion
   - Permissions
      - Client Role 1 and 2 can view only
      - Service provider role 3 can select a technician, include or modify instructions
   - Allowed next states
      - `In Progress` A technician must be allocated. Typically there would already be one, but this can be changed
      - `Completed` A note of explanation is required, images could be added

6. **Cannot repair** - Service Provider Role 4 and Role 3 (Technician) marks work as unrepairable.
   - Permissions 
      - Service provider (role 3 and 4) view only
      - Client role 1 (view only)
      - Client role 2 can change state and edit
   - Allowed next states:
      - `Confirmed` teminate the job
      - `Incomplete` ask service provider to review

7. **Confirmed** - Client representatie (role 1 or 2) accepts that the job is completed. This is a terminal state.
   - No further state changes are possible
   - The job can be archived respectively by Client Role 2 or Service Provider Role 3
   - The job can be viewed by any of the roles

8. **Declined** - The Service provider (role 3) has declined the job
   - Permissions
      - Service Provider (role 3 and 4 ) can view the job while allocated to the service provider
      - Client Role 1 can view
      - Client Role 2 can reallocate the job to another service provider
   - A job in this state must be treated in the same way as the reported state, The difference is that the notes or comments by service providers must be deleted is the job is reassigned to a different service provider
   - Allowed next states 
      - `Rejected`
      - `Quote Requested`
      - `Assigned`

9. **Rejected** - This is a terminal state. The service request is not accepted by the Client admin and no further action is needed

6. **Quote Requested** - The Client has requested a quote
   - Permissions
      - Client (view only)
      - Service Provider (role 3) can change state
   - Allowed next states 
      - `Quote Provided` The quote must be selected before quote can be provided. Use a separate quote view to create quotes and record these, As it it created, it should be automatically linked to this job.
      - `Unable to quote` Optional can be provided

10. **Quote Provided** - 
   - Permissions
      - Client (role 2) can change state and view quote
      - Client (role 1) can view job but cannot view quote
      - Service Provider (role 1) can view job and quote
   - Allowed next states
      - `Assigned` The quote is accepted and the service provider can continue with the job
      - `Quote Requested` The quote details are being quiried. The service provider is asked to revisit the quote. A note to the service provider is required
      - `Rejected` Terminate this job with no further action
      - Re-assign to another service provider by resetting the quote information. In this case it is identical to returning to the initial `Reported`state with cleared service provider quote information. A message is sent to the service provider that his quote was not accepted.

