# QR Code Structure and Parameters

This document outlines the structure and parameters for QR codes used within the Snappy application. The QR codes are designed to be flexible, supporting both in-app scanning and external scanning via a device's native camera.

## URL Structure

All QR codes should point to a URL with the following structure:

```
https://[your-domain]/qr?[parameters]
```

When scanned, this URL directs the user to the appropriate workflow within the Snappy application.

### Example

```
https://snappyapp.co.za/qr?client=3&item=FRIDGE-001&location=12&asset=SN-98765&mngr=5&sp=14
```

## Query String Parameters

The following parameters can be included in the QR code's query string. When a QR code is scanned, any provided parameters will be used to pre-populate the service request form. If a parameter is missing from the URL, the corresponding field will not be populated.

| Parameter  | Description                                                                                             | Database Mapping                                 |
| :--------- | :------------------------------------------------------------------------------------------------------ | :----------------------------------------------- |
| `client`   | The ID of the client organization this asset belongs to. Used for validation.                           | `participants.participantId`                     |
| `item`     | The unique identifier for the asset or item requiring service (e.g., a serial number or asset tag).       | `jobs.item_identifier`                           |
| `location` | The ID of the physical location where the asset is situated.                                            | `locations.id`                                   |
| `asset`    | An internal asset number or tag. (For future use)                                                       | (No direct mapping yet)                          |
| `mngr`     | The ID of the manager or budget controller responsible for approving service requests for this item.      | `jobs.approver_id`                               |
| `sp`       | The ID of the preferred or default service provider for this item. This is a *suggestion* for assignment. | `jobs.assigned_provider_participant_id`          |

## Workflows

### 1. In-App Scanning
When a user is already logged in and uses the scanner within the "Create Service Request" page, the URL parameters are parsed and used to pre-fill the form fields.

### 2. External Scanning
When a user scans a QR code with their device's native camera, they are taken to the URL. The `/qr` route handler will:
- If the user is logged in, redirect them to a pre-filled "Create Service Request" page.
- If the user is not logged in, redirect them to the login page first, and then redirect them to the pre-filled form after a successful sign-in.