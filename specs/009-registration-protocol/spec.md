# Feature Specification: W3RD-Compliant Registration Protocol

**Feature Branch**: `009-registration-protocol`
**Created**: 2026-03-11
**Status**: Draft
**Input**: Integrated from `docs/registration.md`

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Applicant Registration (Priority: P1)

As a new user (Applicant), I want to register for the platform through a step-by-step process so that I can gain access to the system.

**Why this priority**: Core functionality; without this, no new users can be onboarded.

**Independent Test**: Can be tested by simulating a new user entering an email and completing a multi-step form.

**Acceptance Scenarios**:

1. **Given** a new visitor on the registration page, **When** they enter their email, **Then** a "Deferred Identity" registration record is created (no User account yet).
2. **Given** an active registration, **When** the applicant submits data for a step, **Then** the system validates the data and serves the next step in the workflow.
3. **Given** all steps are completed successfully, **When** the final step is submitted, **Then** the registration is "graduated" and a real User account is created.

### User Story 2 - Workflow Configuration (Priority: P2)

As an Administrator, I want to define and modify registration workflows (blueprints) so that I can customize the onboarding experience for different types of users without code changes.

**Why this priority**: Essential for the system's flexibility and multi-tenant/multi-use-case support.

**Independent Test**: Can be tested by creating a new Workflow record with distinct steps and verifying the API serves them in order.

**Acceptance Scenarios**:

1. **Given** an administrator, **When** they define a new Workflow with specific steps, **Then** new registrations can be assigned to that workflow.
2. **Given** a workflow with "Logic Rules" (e.g., skip step if Role=Vendor), **When** an applicant meets the criteria, **Then** the conditional step is skipped automatically.
3. **Given** a workflow with "Risk Rules", **When** a registration request has a high risk score, **Then** a verification step (e.g., CAPTCHA) is dynamically injected.

### User Story 3 - Session Restoration (Priority: P2)

As an Applicant who got interrupted, I want to resume my registration from where I left off using a magic link so that I don't have to re-enter my information.

**Why this priority**: Critical for conversion rates and user experience on multi-device journeys.

**Independent Test**: Can be tested by requesting a magic link, opening it in a new browser, and verifying the state is restored.

**Acceptance Scenarios**:

1. **Given** an in-progress registration, **When** the user requests a "save for later" link, **Then** the system generates a secure, time-limited URL.
2. **Given** a magic link, **When** the user clicks it on a different device, **Then** they are redirected to their current active step with previous data preserved.

### User Story 4 - Team Invitations (Priority: P3)

As an Applicant creating an Organization, I want to invite team members during my registration so that they can be onboarded immediately.

**Why this priority**: Supports "land and expand" growth strategies but is secondary to the primary individual flow.

**Independent Test**: Can be tested by populating the "invited_team" field during registration and verifying child registrations are created.

**Acceptance Scenarios**:

1. **Given** an applicant inviting colleagues, **When** the main registration submits the invitation list, **Then** the system creates "child" registrations for each invitee linked to the parent.
2. **Given** a child registration, **When** the invitee starts their flow, **Then** they are pre-associated with the Organization being created.

### Edge Cases

- **Session Expiry**: What happens when a user returns to a registration after the `expires_at` timestamp? (System should require restarting or refreshing the session).
- **Step Validation Failure**: How does the system handle invalid input on a specific step? (System returns the same step fragment with error messages inline).
- **Concurrent Updates**: What happens if the workflow definition changes while a user is in the middle of it? (Versioning strategy ensures existing registrations continue on their original workflow ID).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST support a "Deferred Identity" model where data is stored in a temporary `Registration` entity until all workflow requirements are met.
- **FR-002**: System MUST utilize a "Workflow Engine" to determine the sequence of steps based on a predefined configuration.
- **FR-003**: The Workflow Engine MUST support conditional "Logic Rules" to skip steps based on input data (e.g., Role selection).
- **FR-004**: The Workflow Engine MUST support "Risk Rules" that dynamically inject verification steps based on external risk scores (e.g., IP reputation).
- **FR-005**: System MUST provide a Hypermedia API (HATEOAS-like) where the response to a step submission includes the HTML fragment for the next step.
- **FR-006**: System MUST encrypt sensitive `form_data` stored in the temporary registration record.
- **FR-007**: System MUST support "Magic Link" generation to allow users to resume sessions across devices.
- **FR-008**: System MUST track "Step Timings" (analytics) to measure how long users spend on each specific step.
- **FR-009**: System MUST support a "Manual Review" state where the workflow pauses until an Admin approves the registration.
- **FR-010**: System MUST support "Data Enrichment" steps that asynchronously query external providers to populate form data.
- **FR-011**: Upon successful completion of all steps ("Graduation"), the system MUST atomically create the target User/Account records and archive the Registration.
- **FR-012**: System MUST emit webhook events for key lifecycle stages (started, step_completed, abandoned, graduated).
- **FR-013**: System MUST allow Clients to define custom themes (colors, fonts) which are injected as CSS variables into the API responses.

### Key Entities

- **Workflow**: A blueprint defining the sequence of steps, logic rules, and client association.
- **WorkflowStep**: A distinct unit of the process (Form, Game, Content) with specific UI templates and validation rules.
- **Registration**: A stateful, temporary record tracking an applicant's progress, accumulated data, and current status within a Workflow.
- **Client**: The tenant or application consuming the registration protocol.

## Success Criteria *(mandatory)*

1.  **Flexibility**: Admins can reorder or add steps to a workflow configuration without requiring code deployments.
2.  **Conversion Visibility**: System provides granular timing data for every step, allowing identification of drop-off points.
3.  **Security**: No permanent User records are created for abandoned or rejected applications, keeping the main user table clean.
4.  **Resilience**: Users can switch devices mid-registration without losing progress via secure magic links.
