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


