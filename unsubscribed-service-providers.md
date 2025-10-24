# Service Providers

## General Overview

The Snappy platform provides services to Clients (or Owners of Assets) that must be kept working, i.e. must be repaired and maintained by Service Providers (SP). To get maximum benefit, Service Providers also subscribe, This gives many Clients and many SPs the ability to coordinate their needs and services via a common ticketing platform, with many benefits to subscribers. Some Clients nevertheless, have service providers that already run their own internal job ticket management system and understandably would be unwilling to change their way of dealing with their clients, which they do via e.g.  email, call centre or whatsapp. 

There are therefore two classes of SPs:

- Integrated or **platform** SPs. For these SPs, their job ticket state and all interactions are managed and controlled by the platform

- **External** SPs onm the other hand, because they use their own systems, can only be managed by proxy creating a 'shadow' or 'proxy' participant that is Client specific.

## Participants

Currently participants are of two types, `S` and `C`.  These must be expanded to:

* `S` - Service Providers. These are the current integrated **platform service providers**

* `C`  - Clients. These are the current clients, i.e. the participants that require services

* `XS` - **External Service Providers**. This is a new third type of participant. This 'participant' is inactive and is not subscribed. The 'exchanges', states, etc, are managed entirely by the Client user (role 2) who effectively can fill out any of the information normally completed by the SP role 3 and 4 users.



The participant-type table defines for each participant, their 'Active' status and the participant types



## Service Provider Approval

Clients can currently allocate job tickets only to approved service providers. These are managed using the participant_approval table that associates Clients to Service Providers.

The service providers are added using a modal that allows selection from a list of available SPs. SPs can also be invited and are added automatically when they accept an invitation form a Client.

We must now allow for the addition of **External Service Providers** via a button on the client dashboard Approved Service Providers section. A modal is needed that allows the pertinent information of the `XS` SP to be captured. The same modal could also be used to edit the `XS` SP details once the Service Provider has been added. 

When the `XS` SP is added, the entry to link the service provider to the client in the participant_approval table must also subsequently be created.

Th service provider card must clearly show which SPs are integrated or platform SPs and which are external.

## Job Ticket Management

The assignment process of a role 2 user of the Client occurs when the state of the job ticket is `Reported`.     At this stage, the list of participants to which the ticket may be assigned, should clearly show which are Platform providers and which are external `XS` providers.

For as long as the job ticket is assigned to and `XS` provider, the role 2 user must have ability to change states as if the user was of role 3 or 4 of the `XS` provider. In so doing, the user acts as a proxy for that provider.