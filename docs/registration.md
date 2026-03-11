# Technical Specification: W3RD-Compliant Registration Protocol

**System Role:** Hypermedia Provider (Laravel Backend)  
**Consumer Role:** Distributed AHA Stack (Astro/HTMX/Alpine)  
**Protocol:** W3RD.io (Stateless Hypermedia over HTTPS)  
**Base Namespace:** `/htmx/v1/registration/`

---

## 1. Core Concept: The "Holding Pen"

The system utilizes a **"Deferred Identity"** pattern. No `User` record is created until a `Registration` entry clears the Workflow requirements defined by the specific `Client`. The backend serves HTML fragments (Hypermedia) that the remote AHA sites inject into their DOM.

---

## 2. Database Schema (MySQL)

### 2.1 `workflows`
The high-level blueprint for a specific process (e.g., User Onboarding, Vendor Application).

| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt (PK) | Internal Primary Key |
| `client_uuid` | UUID (Index) | Public ID of the Client (Tenant) |
| `name` | String | e.g., "Standard Onboarding", "VIP Intake" |
| `category` | String | Categorization (default: `registration`) |
| `is_active` | Boolean | Global toggle for this blueprint |

### 2.2 `workflow_steps`
The atomic units of the workflow. Each represents a unique Blade fragment.

| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt (PK) | Internal Primary Key |
| `workflow_id` | ForeignID | Parent Workflow ID |
| `slug` | String (Index) | URL identifier (e.g., `personal-info`, `id-upload`) |
| `type` | Enum | `form`, `kyc`, `gate`, `review`, `payment`, `info` |
| `blade_view` | String | Path to Blade file (e.g., `htmx.registration.steps.kyc`) |
| `logic_rule` | JSON | Conditional skip rules (Feature #1) |
| `requires_approval`| Boolean | If true, triggers Feature #10 (Manual Review) |
| `sort_order` | Integer | Sequence within the workflow |

### 2.3 `registrations`
The state-machine table for in-progress applications.

| Field | Type | Description |
| :--- | :--- | :--- |
| `uuid` | UUID (PK) | W3RD Context ID (Used in all HTMX requests) |
| `client_id` | ForeignID | FK to the Clients table |
| `workflow_id` | ForeignID | The blueprint being followed |
| `current_step_id` | ForeignID | FK to workflow_steps |
| `email` | String | Captured email (Unique within Client scope) |
| `form_data` | JSON | Encrypted blob of all step inputs (#3, #5) |
| `status` | Enum | `draft`, `pending_review`, `approved`, `graduated` |
| `intended_role` | String | Target User Role (Feature #4) |
| `approved_by` | ForeignID | Admin User ID who authorized graduation (#10) |
| `expires_at` | Timestamp | TTL for abandoned registrations |

---

## 3. The W3RD Protocol & URI Logic

### 3.1 Endpoint Mapping

All hypermedia requests follow this pattern:
`{BASE_URL}/htmx/v1/registration/{step_slug}`

*   **GET**: Fetches the fragment for the specific step. Requires `X-W3RD-Context` (Registration UUID).
*   **POST**: Submits data for the current step. Laravel processes data, updates `form_data` JSON, and determines the next `step_slug`.

### 3.2 W3RD Header Requirements

| Header | Requirement | Purpose |
| :--- | :--- | :--- |
| `X-W3RD-Client` | **Mandatory** | The `client_uuid` of the requesting site |
| `X-W3RD-Context` | **Mandatory** | The `registration_uuid` (Context ID) |
| `HX-Request` | **Mandatory** | Ensures the request is from HTMX |
| `HX-Trigger` | Optional (Response) | Triggers Alpine.js events on the AHA frontend |

---

## 4. Functional Logic Specifications

### 4.1 Feature #1: Conditional Skip Logic
Before serving a fragment, the `WorkflowEngine` evaluates the `logic_rule`.

*   **Rule Format**: `{"field": "intended_role", "operator": "==", "value": "vendor"}`
*   **Behavior**: If the rule fails (e.g., user is a 'buyer'), the engine skips the current `step_slug` and automatically redirects the HTMX request to the next valid slug using the `HX-Redirect` or `HX-Push-Url` header.

### 4.2 Feature #4 & #5: Branching and Team Provisioning
*   **Branching**: The workflow can branch based on `intended_role`. Different roles follow different `sort_order` paths.
*   **Team Provisioning**: The `form_data` JSON supports an `invited_team` array. Upon "Graduation," the backend loops through this array to create multiple `User` records under the same Client.

### 4.3 Feature #10: Manual Review (Hypermedia Hold)
If a step is flagged `requires_approval`:
1.  The backend updates `registration.status` to `pending_review`.
2.  The response is a "Review in Progress" Blade fragment.
3.  The fragment uses `hx-get` with a `trigger="every 10s"` to poll the backend.
4.  Once an Admin approves, the next poll returns the "Success/Graduation" fragment.

---

## 5. Security & Graduation Specification

### 5.1 Promotion to User (Graduation)
The "Promotion" is an atomic transaction:
1.  Validate that all required steps in the Workflow are marked as complete in `form_data`.
2.  Map `form_data` keys to `User` and `Profile` table columns.
3.  Create `User` record(s).
4.  Delete or Archive the `Registration` record (or mark as `graduated`).

### 5.2 W3RD Handover
Post-graduation, the backend returns a final fragment containing a **one-time-use Handover Token**. The AHA frontend uses this token to establish the first authenticated session on the Client's specific domain.

---

## 6. Error Handling

### 6.1 Validation Errors
Return `422 Unprocessable Entity`. The body must be the same form fragment with `$errors` populated.

### 6.2 Expired Context
If `X-W3RD-Context` is expired or invalid, return `403 Forbidden` with a `HX-Trigger: registration-expired` header to force a restart on the AHA site.

