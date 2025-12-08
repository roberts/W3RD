# **Gig Management and Logic Specification (V1)**

**Base Resource:** /api/v1/gigs **Primary Model:** Gig (identified by **ULID**)

## **1\. Core Gig Endpoints**

These endpoints manage the creation, listing, and cancellation of the primary work unit.

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **GET** | /gigs | **Logic:** GigService::list() handles filtering, pagination, and sorting. It eager-loads the relevant pricing model (Bounty, Hourly, or Commission details). | **Query Params:** status, fulfillment\_type, pricing\_model, min\_budget, max\_budget, category, sort. |
| **GET** | /gigs/{gig.ulid} | **Logic:** GigService::find($ulid). Uses a detailed GigResource to return the Gig along with all nested relationships (Tasks, current Bids/Hunter, Location status). | None |
| **POST** | /gigs | **Logic:** 1\. **Validation:** CreateGigRequest ensures only ONE pricing model is present (hourly\_rate OR commission\_rate OR bounty\_details). 2\. **GigService::create():** Creates the base Gig record. 3\. **Conditional Creation:** If bounty\_details is present, BountyService::create() is called to create the related Bounty model and set initial status to OPEN. | **Body (JSON):** title, description, fulfillment\_type, category, tags. **AND one of:** hourly\_rate, commission\_rate, OR bounty\_details (object). |
| **POST** | /gigs/{gig.ulid}/cancel | **Logic:** 1\. **Authorization:** Policy check (canCancel) ensures only the Poster can initiate. 2\. **State Check:** Must be in OPEN, ASSIGNED, or IN\_PROGRESS. 3\. **Escrow Check:** If a Bounty exists, EscrowService::refundFunds() is called. 4\. **GigService::updateStatus('CANCELED')**. | **Body (JSON):** reason (string, required for audit). |
| **POST** | /gigs/{gig.ulid}/approve | **Logic:** 1\. **Authorization:** Policy check (canApprove). 2\. **State Check:** Must be in FULFILLMENT\_PENDING. 3\. **Payment Logic:** If Bounty exists, EscrowService::releaseFunds(). If Hourly/Commission, triggers variable pay calculation via PaymentService. 4\. **GigService::updateStatus('COMPLETE')**. | None |

### **Gig State Machine**

## **2\. Nested Task Management Endpoints**

The Task model allows Gigs to be broken down into discrete steps, assignable to either humans or AI agents.

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **GET** | /gigs/{gig.ulid}/tasks | **Logic:** TaskService::listByGig(). Returns TaskResource collection ordered by sequence\_number. | None |
| **POST** | /gigs/{gig.ulid}/tasks | **Logic:** 1\. **Authorization:** Only Poster or awarded Hunter can add tasks if status is \< IN\_PROGRESS. 2\. **TaskService::create():** Creates a new Task. 3\. **Validation:** If performer\_type is ai\_agent, must validate x402\_agent\_id against the Agent Registry. | **Body (JSON):** title, description, performer\_type (human or ai\_agent), x402\_agent\_id (ULID, nullable), bounty\_ulid (ULID, nullable, links to a specific Bounty on this Gig). |
| **PATCH** | /gigs/{gig.ulid}/tasks/{task.ulid} | **Logic:** 1\. **Authorization:** Only the assigned Hunter or the relevant AI Agent service can update the Task. 2\. **TaskService::updateStatus():** Updates status to COMPLETED. 3\. **Conditional Trigger:** If performer\_type=ai\_agent and status is COMPLETED, triggers **X402Service::payAgent($task)**. | **Body (JSON):** status (completed), details (notes on completion). |

## **3\. Fulfillment, Tracking, and Legal Endpoints**

These endpoints support the physical and high-value legal requirements for expansion.

### **3.1. Geolocation and Tracking (Physical Gigs)**

| HTTP Method | Endpoint | Application Logic & Processing | Request Body / Query Params |
| :---- | :---- | :---- | :---- |
| **POST** | /gigs/{gig.ulid}/location/update | **Logic:** 1\. **Authorization:** Must be the awarded Hunter. 2\. **Rate Limiting:** Throttle requests to one update every 5 seconds. 3\. **GigService::logLocation():** Stores the point in a dedicated LocationHistory table. | **Body (JSON):** latitude, longitude, timestamp. |
| **GET** | /gigs/{gig.ulid}/location | **Logic:** GigService::getRecentPath(). Retrieves location history for the Poster to view on a map (for real-time tracking). | **Query Params:** start\_time, end\_time (optional). |
| **POST** | /gigs/{gig.ulid}/geofence/define | **Logic:** 1\. **Authorization:** Poster only. 2\. **GigService::setGeofence():** Stores the defined boundary coordinates in the Gig model. 3\. **Background Job:** Starts a job to periodically check the Hunter's location against the defined fence. | **Body (JSON):** coordinates (array of points) OR center\_lat, center\_lon, radius\_meters. |

### **3.2. Proof-of-Fulfillment (PoF)**

| HTTP Method | Endpoint | Action | Application Logic & Processing |
| :---- | :---- | :---- | :---- |
| **POST** | /gigs/{gig.ulid}/fulfillment/proof | **Logic:** 1\. **File Storage:** Uploads and securely stores the image/signature in S3/Cloud Storage. 2\. **Verification:** Validates geotag data against the Gig's expected location. 3\. **GigService::submitFulfillmentProof():** Updates status to FULFILLMENT\_PENDING. | **Body (Multipart):** image\_file, signature\_image (optional), notes. |

### **3.3. Dynamic Contracts and Documents**

| HTTP Method | Endpoint | Action | Application Logic & Processing |
| :---- | :---- | :---- | :---- |
| **GET** | /gigs/{gig.ulid}/documents | **Logic:** 1\. **ContractService::listDocuments():** Returns metadata/links for all generated documents (Contract, NDA, Completion Cert). 2\. **Authorization:** Restricts access based on user role (Poster/Hunter). | None |
| **POST** | /gigs/{gig.ulid}/documents/generate | **Logic:** 1\. **ContractService::generateContract():** Generates a PDF/Markdown contract using a templating engine (based on Gig type and user jurisdiction). 2\. Stores the document securely. | **Body (JSON):** document\_type (agreement, nda), jurisdiction. |

## **4\. Bounty-Specific Logic Endpoints**

These are specialized actions that interact with the nested Bounty model and EscrowService.

| HTTP Method | Endpoint | Action | Application Logic & Processing |
| :---- | :---- | :---- | :---- |
| **POST** | /gigs/{gig.ulid}/bounties/{bounty.ulid}/tasklist/draft | **Logic:** 1\. **Authorization:** Hunter only. 2\. **BountyService::updateTaskList():** Stores the new markdown as a draft. 3\. **Event Dispatch:** Dispatches an event to notify the Poster of the new draft. | **Body (JSON):** task\_list\_markdown. |
| **POST** | /gigs/{gig.ulid}/bounties/{bounty.ulid}/tasklist/approve | **Logic:** 1\. **Authorization:** Poster only. 2\. **BountyService::approveTaskList():** Promotes the current draft to the official Task List. 3\. **CRITICAL:** Sets Gig status to IN\_PROGRESS and logs task\_list\_approved\_at. | None |
| **POST** | /gigs/{gig.ulid}/bounties/{bounty.ulid}/work/submit | **Logic:** 1\. **Authorization:** Hunter only. 2\. Records the GitHub PR URL. 3\. Sets Gig status to FULFILLMENT\_PENDING. | **Body (JSON):** pull\_request\_url. |

