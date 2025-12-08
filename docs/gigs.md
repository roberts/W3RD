# **Gig Management and Logic Specification (V1)**

**Base Resource:** /api/v1/gigs **Primary Model:** Gig (identified by **ULID**)

## **1. Core Gig Endpoints**

These endpoints manage the creation, listing, and cancellation of the primary work unit.

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **GET** | /gigs | **Logic:** GigService::list() handles filtering, pagination, and sorting. It eager-loads the relevant pricing model (Bounty, Hourly, or Commission details). | **Query Params:** status, fulfillment_type, pricing_model, min_budget, max_budget, category, sort. |
| **GET** | /gigs/search | **Logic:** Uses Meilisearch/Algolia index for high-performance fuzzy search on title, description, and tags. | **Query Params:** q (search term), filters (category, budget). |
| **GET** | /gigs/{gig.ulid} | **Logic:** GigService::find($ulid). Uses a detailed GigResource to return the Gig along with all nested relationships (Tasks, current Bids/Hunter, Location status). | None |
| **POST** | /gigs | **Logic:** 1. **Validation:** CreateGigRequest ensures only ONE pricing model is present (hourly_rate OR commission_rate OR bounty_details). 2. **GigService::create():** Creates the base Gig record. 3. **Conditional Creation:** If bounty_details is present, BountyService::create() is called to create the related Bounty model and set initial status to OPEN. | **Body (JSON):** title, description, fulfillment_type, category, tags. **AND one of:** hourly_rate, commission_rate, OR bounty_details (object). |
| **PATCH** | /gigs/{gig.ulid}/publish | **Logic:** Transitions a DRAFT Gig to OPEN. Validates all required fields are present before going live. | None |
| **POST** | /gigs/{gig.ulid}/cancel | **Logic:** 1. **Authorization:** Policy check (canCancel) ensures only the Poster can initiate. 2. **State Check:** Must be in OPEN, ASSIGNED, or IN_PROGRESS. 3. **Escrow Check:** If a Bounty exists, EscrowService::refundFunds() is called. 4. **GigService::updateStatus('CANCELED')**. | **Body (JSON):** reason (string, required for audit). |
| **POST** | /gigs/{gig.ulid}/approve | **Logic:** 1. **Authorization:** Policy check (canApprove). 2. **State Check:** Must be in FULFILLMENT_PENDING. 3. **Payment Logic:** If Bounty exists, EscrowService::releaseFunds(). If Hourly/Commission, triggers variable pay calculation via PaymentService. 4. **GigService::updateStatus('COMPLETE')**. | None |
| **POST** | /gigs/{gig.ulid}/revisions | **Logic:** Rejects the current fulfillment and requests changes. Transitions status back to IN_PROGRESS. | **Body (JSON):** comments (required), required_changes (list). |
| **POST** | /gigs/{gig.ulid}/dispute | **Logic:** Freezes the Gig and Escrow. Notifies admins. Transitions status to DISPUTED. | **Body (JSON):** reason, evidence_text. |
| **POST** | /gigs/{gig.ulid}/review | **Logic:** Submit a rating/review for the counterparty after completion. Updates user reputation stats. | **Body (JSON):** rating (1-5), comment. |

### **Gig State Machine**

## **2. Bids & Negotiation (New)**

Manages the application process before a Gig is assigned.

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **GET** | /gigs/{gig.ulid}/bids | **Logic:** Lists all bids for a Gig. Posters see all; Hunters see only their own. | None |
| **POST** | /gigs/{gig.ulid}/bids | **Logic:** Hunter submits a proposal. Validates Hunter meets requirements (reputation, skills). | **Body (JSON):** amount (if negotiable), pitch, estimated_completion_date. |
| **POST** | /gigs/{gig.ulid}/bids/{bid.ulid}/accept | **Logic:** Poster accepts a bid. Assigns Hunter to Gig. Transitions Gig status to ASSIGNED. | None |

## **3. Nested Task & Milestone Management**

The Task model allows Gigs to be broken down into discrete steps, assignable to either humans or AI agents. Milestones allow for partial payouts.

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **GET** | /gigs/{gig.ulid}/tasks | **Logic:** TaskService::listByGig(). Returns TaskResource collection ordered by sequence_number. | None |
| **POST** | /gigs/{gig.ulid}/tasks | **Logic:** 1. **Authorization:** Only Poster or awarded Hunter can add tasks if status is \< IN_PROGRESS. 2. **TaskService::create():** Creates a new Task. 3. **Validation:** If performer_type is ai_agent, must validate x402_agent_id against the Agent Registry. | **Body (JSON):** title, description, performer_type (human or ai_agent), x402_agent_id (ULID, nullable), bounty_ulid (ULID, nullable, links to a specific Bounty on this Gig). |
| **POST** | /gigs/{gig.ulid}/milestones | **Logic:** Creates a payment milestone linked to specific tasks. | **Body (JSON):** title, amount, task_ids (array). |
| **PATCH** | /gigs/{gig.ulid}/tasks/{task.ulid} | **Logic:** 1. **Authorization:** Only the assigned Hunter or the relevant AI Agent service can update the Task. 2. **TaskService::updateStatus():** Updates status to COMPLETED. 3. **Conditional Trigger:** If performer_type=ai_agent and status is COMPLETED, triggers **X402Service::payAgent($task)**. | **Body (JSON):** status (completed), details (notes on completion). |

## **4. Fulfillment, Tracking, and Legal Endpoints**

These endpoints support the physical and high-value legal requirements for expansion.

### **4.1. Attachments & Proof**

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **POST** | /gigs/{gig.ulid}/attachments | **Logic:** Unified file upload for contracts, briefs, and proof-of-work. Stores in S3. | **Body (Multipart):** file, type (contract, brief, proof, invoice). |

### **4.2. Geolocation and Tracking (Physical Gigs)**

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **POST** | /gigs/{gig.ulid}/location/update | **Logic:** 1. **Authorization:** Must be the awarded Hunter. 2. **Rate Limiting:** Throttle requests to one update every 5 seconds. 3. **GigService::logLocation():** Stores the point in a dedicated LocationHistory table. | **Body (JSON):** latitude, longitude, timestamp. |
| **GET** | /gigs/{gig.ulid}/location | **Logic:** GigService::getRecentPath(). Retrieves location history for the Poster to view on a map (for real-time tracking). | **Query Params:** start_time, end_time (optional). |
| **POST** | /gigs/{gig.ulid}/geofence/define | **Logic:** 1. **Authorization:** Poster only. 2. **GigService::setGeofence():** Stores the defined boundary coordinates in the Gig model. 3. **Background Job:** Starts a job to periodically check the Hunter's location against the defined fence. | **Body (JSON):** coordinates (array of points) OR center_lat, center_lon, radius_meters. |

### **4.3. Dynamic Contracts and Documents**

| HTTP Method | Endpoint | Action | Application Logic & Processing |
| :---- | :---- | :---- | :---- |
| **GET** | /gigs/{gig.ulid}/documents | **Logic:** 1. **ContractService::listDocuments():** Returns metadata/links for all generated documents (Contract, NDA, Completion Cert). 2. **Authorization:** Restricts access based on user role (Poster/Hunter). | None |
| **POST** | /gigs/{gig.ulid}/documents/generate | **Logic:** 1. **ContractService::generateContract():** Generates a PDF/Markdown contract using a templating engine (based on Gig type and user jurisdiction). 2. Stores the document securely. | **Body (JSON):** document_type (agreement, nda), jurisdiction. |

## **5. Bounty-Specific Logic Endpoints**

These are specialized actions that interact with the nested Bounty model and EscrowService.

| HTTP Method | Endpoint | Action | Application Logic & Processing |
| :---- | :---- | :---- | :---- |
| **POST** | /gigs/{gig.ulid}/bounties/{bounty.ulid}/tasklist/draft | **Logic:** 1. **Authorization:** Hunter only. 2. **BountyService::updateTaskList():** Stores the new markdown as a draft. 3. **Event Dispatch:** Dispatches an event to notify the Poster of the new draft. | **Body (JSON):** task_list_markdown. |
| **POST** | /gigs/{gig.ulid}/bounties/{bounty.ulid}/tasklist/approve | **Logic:** 1. **Authorization:** Poster only. 2. **BountyService::approveTaskList():** Promotes the current draft to the official Task List. 3. **CRITICAL:** Sets Gig status to IN_PROGRESS and logs task_list_approved_at. | None |
| **POST** | /gigs/{gig.ulid}/bounties/{bounty.ulid}/work/submit | **Logic:** 1. **Authorization:** Hunter only. 2. Records the GitHub PR URL. 3. Sets Gig status to FULFILLMENT_PENDING. | **Body (JSON):** pull_request_url. |
