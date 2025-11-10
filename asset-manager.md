# Asset Manager - Detailed Plan (Revised 2)

This document outlines the plan for creating an Asset Manager. This feature is intended for administrators to manage assets and generate QR codes for them, with a strict separation of responsibilities and specific UI/data logic.

---

## 1. User Roles, Permissions, and Editing Logic

Asset lists are **independent and mutually exclusive** based on who creates them.

*   **Role 2: Client Administrator**
    *   **Responsibility:** Manages an independent asset list for their own client participant.
    *   **Permissions:** Can create, edit, upload, download, and print the asset list owned by their client participant. This list is **not available for editing** by any service provider.
    *   **Editing Logic:**
        *   When editing an asset, the `sp_id` (Service Provider ID) is optional.
        *   The UI will present a dropdown of the client's approved service providers to select from.

*   **Role 3: Service Provider Administrator**
    *   **Responsibility:** Optionally manages an independent asset list for each client for whom the service provider is approved.
    *   **Permissions:** Can select a client, then create, edit, upload, download, and print an asset list for that client. This list is **not available for editing** by the client users or any other service providers.
    *   **Editing Logic:**
        *   When creating or editing an asset, the `sp_id` field is automatically populated with the service provider's own participant ID and is read-only.
        *   The `location_id` field must be selected from a dropdown list of the **client's defined locations**.
        *   The `manager_id` field must be selected from a dropdown list of the **client's defined Role 2 users**.

---

## 2. Database Schema

The `assets` table will use a `list_owner_id` to enforce the strict editing and ownership rules. The schema remains the same as the last revision.

**`CREATE TABLE` Statement:**
```sql
CREATE TABLE `assets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `list_owner_id` INT NOT NULL COMMENT 'Participant ID of the list owner (either the client or a service provider). Determines who can edit the record.',
  `client_id` INT NOT NULL COMMENT 'Participant ID of the client that owns the asset.',
  `asset_no` VARCHAR(255) NOT NULL,
  `item` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `location_id` INT NULL COMMENT 'Site ID where the item is located',
  `manager_id` INT NULL COMMENT 'User ID of the manager responsible for approvals',
  `sp_id` INT NULL COMMENT 'Optional: A default Service Provider ID to associate with the asset',
  `star` BOOLEAN DEFAULT FALSE,
  `status` VARCHAR(50) DEFAULT 'active' COMMENT 'e.g., active, inactive, unavailable, decommissioned',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_asset_in_list` (`list_owner_id`, `client_id`, `asset_no`),
  FOREIGN KEY (`list_owner_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`location_id`) REFERENCES `client_locations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sp_id`) REFERENCES `participants`(`id`) ON DELETE SET NULL
) COMMENT='Stores asset information. Editing is controlled by list_owner_id.';
```

---

## 3. Development Phases

### Phase 1: Core Backend (Database and API)

**Goal:** Create the foundational database and API, including helper endpoints for UI dropdowns.

*   **Tasks:**
    1.  **Schema:** Add the `assets` table to the database.
    2.  **Primary Asset API Endpoints:**
        *   `POST /api/assets`: Create an asset. `list_owner_id` is the authenticated user's participant ID.
        *   `GET /api/assets?client_id={id}`: List assets for a client, grouped by `list_owner_id`.
        *   `PUT /api/assets/{id}`: Update an asset. **Must reject if user is not the `list_owner_id`.**
        *   `DELETE /api/assets/{id}`: Delete an asset. **Must reject if user is not the `list_owner_id`.**
    3.  **Helper API Endpoints (for UI dropdowns):**
        *   `GET /api/client-locations?client_id={id}`: Returns a list of locations for the specified client.
        *   `GET /api/client-users?client_id={id}&role=2`: Returns a list of Role 2 users for the specified client.
        *   `GET /api/service-providers?client_id={id}`: Returns a list of approved service providers for the specified client.
    4.  **Testing:** Rigorously test all endpoints, especially the ownership/permission logic.

### Phase 2: Client Dashboard (Frontend for Role 2)

**Goal:** Build the UI for Client Administrators, incorporating the required data lookups.

*   **Tasks:**
    1.  **Asset List View:** Display the list where `list_owner_id` is the client's own ID.
    2.  **Add/Edit Modal:**
        *   When editing, the form will include an optional dropdown for "Service Provider".
        *   This dropdown will be populated by calling `GET /api/service-providers`.
    3.  **CSV Upload:** Implement `POST /api/assets/upload_csv`.

### Phase 3: Service Provider Dashboard (Frontend for Role 3)

**Goal:** Build the UI for Service Providers, incorporating the required data lookups.

*   **Tasks:**
    1.  **Client Selection:** User selects a client.
    2.  **Asset List View:** Display the list where `client_id` is the selected client and `list_owner_id` is the SP's ID.
    3.  **Add/Edit Modal:**
        *   The "Service Provider" field will be read-only and auto-filled.
        *   The "Location" dropdown will be populated by calling `GET /api/client-locations`.
        *   The "Manager" dropdown will be populated by calling `GET /api/client-users?role=2`.

### Phase 4: QR Code Generation

**Goal:** Implement QR code generation. (No changes from previous revision).

*   **Tasks:**
    1.  **Backend:** Create endpoint `GET /api/assets/qr_codes?ids={id1,id2,...}`.
    2.  **Frontend:** Allow asset selection and provide a print preview page.

---

## 4. CSV Upload Format

The CSV file must use the exact numeric IDs for relationships. The backend will not perform name-to-ID lookups on uploads.

**Header Row is Required.**

**Columns:**
*   `asset_no` (Required)
*   `item` (Required)
*   `description`
*   `location_id` (Numeric ID from `client_locations` table)
*   `manager_id` (Numeric ID of the user)
*   `sp_id` (Numeric ID of the service provider participant)

**Example:**
```csv
asset_no,item,description,location_id,manager_id,sp_id
COMP-001,"Dell Laptop","15-inch, 16GB RAM",101,205,302
PUMP-05,"Water Pump","5HP Centrifugal",102,205,
```
In the example above, the second asset is not assigned a service provider. The `list_owner_id` for all records will be set based on the user who performs the upload.
