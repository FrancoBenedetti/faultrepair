# Enrolment by invitation

## Purpose

- Allow service provide admins to invite a customer to sign up to use this application
- Allow clients budget controllers to invite a service provider to sign up to use this application
- Invitations will be sent by the user via Whatsapp or Telegram

## Invitation Workflow

    * User is signed in and selects a page to Invite a Client/Service Provider
    
    * Details are provided by the user
        * Whether telegram, whatsapp, sms or email is to be used
        * Contact details of party to be invite
            * First and last name
            * Mobile number
            * Email address
        * Details of user (from the registered values in the system, and therefore do not have to be captured)
            * First and last name
            * Mobile number
            * Business name
            * Business address
            * Service provider id
    * The user submits the request to the system
    * The system stores the details of the invitation, generates a token, creates a sample invitation and a link with the token included
    * The user confirms
    * The whatsap, telegram or email is link is created and inserted for the user or sent as is allowed
    * The recipient recieves the message and clicks the link
    * The link timer expiry time fires linked to the recipient
    * A form with details of the user is presented with invitators details and the recipients details already partially completed, with instructions to submit. The form likely includes particulars of the recipient such as business or home address, capacity (as a buisness or personal, VAT number, ID number of person or business)
    * The form is completed by the recipient and the account is registered with the service provider as approved by the client

## Business Rules

    * In the case where an invitation is sent by a service provider to a prospective client, and where the client is already a member i.e. is already registered as a client, then the client should be asked for approval to declare the referring service provider as an approved service provider. However, if the invited client is not an budget controller user of the client provider, e.g. the user is a technician, then the user must be informed that he does not have the authorisation to add the the service provider, and that this action must be completed by a budget controller.
    * In the case where an invitation is sent by a client to a prospective service provider, and where the service provider is already a member, then the service provider must be informed that no action was performed but that the client must be informed to add the service provider as a service provider budget controller, using their dashboard ability.
    * When a new unenrolled service provider is invited by a budget controller, the servise provider must be automatically added as an approved service provider, once the service provider has enrolled.
    * When a service provider is already a member, not registration is needed, if the inviting user is a budget controller of a client, the service provider is automatically added as an approved service provider.
