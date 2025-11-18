# Research: Agent Implementation

This document consolidates research and decisions made to resolve ambiguities and define the technical approach for the Agent Implementation feature.

## 1. Agent Action Calculation Strategy

-   **Decision**: Game action calculations for agents will be executed asynchronously in a queued background job (`CalculateAgentAction`).
-   **Rationale**: The feature specification requires a random, human-like delay (1-8 seconds) before an agent's action is applied. Performing this `sleep` operation synchronously within the main request cycle would block the game engine and lead to a poor user experience and server resource exhaustion. A background job isolates this delay and allows the main application to remain responsive.
-   **Alternatives Considered**:
    -   **Synchronous Execution with `sleep()`**: Rejected due to blocking behavior and negative impact on server performance and scalability.
    -   **Frontend Delay**: The server could send the action to the human opponent's client immediately, and the client could wait to display it. Rejected because it would create state inconsistencies between the server and clients and would not work for games where two agents play each other.

## 2. Agent Configuration Management

-   **Decision**: Agent capabilities (difficulty, game compatibility, mode overrides) will be stored directly in the `agents` table in the database using `JSON` columns for `supported_game_titles` and `configuration`.
-   **Rationale**: The specification requires that administrators can configure agents without a code deployment. A database-driven approach is the most flexible and meets this requirement perfectly. It allows for dynamic, real-time updates to the agent population.
-   **Alternatives Considered**:
    -   **Configuration Files**: A `config/agents.php` file was considered. Rejected because it would require a code deployment to update agent configurations, failing a key requirement.
    -   **Relational Tables for Configuration**: Using separate pivot tables (e.g., `agent_game_title`, `agent_game_mode`) was considered. While relationally pure, it adds significant complexity to queries for a feature where the configuration data is read but not frequently queried in complex ways. The JSON approach is simpler for this use case and sufficient for the scale outlined in the technical context.

## 3. Service Architecture

-   **Decision**: The agent logic will be split into two distinct services: `AgentSchedulingService` and `AgentService`.
-   **Rationale**: This follows the Single Responsibility Principle.
    -   `AgentSchedulingService` has one job: find an available and compatible agent user. Its logic is focused on querying and filtering based on time, availability, and game type.
    -   `AgentService` has a different job: orchestrate an existing agent's turn. Its logic is focused on dispatching jobs and interacting with a specific `Agent` instance.
-   **Alternatives Considered**:
    -   **A Single `AgentService`**: Combining both functions into one service was considered. Rejected because it would conflate the "finding" and "acting" concerns, making the service larger and harder to test and maintain.
