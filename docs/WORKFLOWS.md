# AgriLink Uganda — Workflows & Database Design

Reference diagrams for the team. These render natively on GitHub (no plugins needed) since they use fenced ```mermaid blocks. Pair with [`schema.sql`](./schema.sql) for the full DDL.

## Table of Contents
- [Entity Relationship Diagram](#entity-relationship-diagram)
- [1. Farmer Registration & Produce Listing](#1-farmer-registration--produce-listing)
- [2. Buyer Registration & Requirement Posting](#2-buyer-registration--requirement-posting)
- [3. Automated Matching (Direct)](#3-automated-matching-direct)
- [4. Produce Aggregation](#4-produce-aggregation)
- [5. Market Price Comparison & Recommendation](#5-market-price-comparison--recommendation)
- [6. Notifications & Alerts](#6-notifications--alerts)

---

## Entity Relationship Diagram

Fifteen tables covering users/role profiles, market intelligence, supply and demand, matching, aggregation, transactions and notifications.

```mermaid
erDiagram
    DISTRICTS ||--o{ USERS : "located in"
    DISTRICTS ||--o{ MARKETS : "located in"
    USERS ||--o| FARMERS : "is a"
    USERS ||--o| BUYERS : "is a"
    USERS ||--o| MARKET_OFFICERS : "is a"
    MARKETS ||--o{ MARKET_OFFICERS : "assigned to"
    MARKETS ||--o{ MARKET_PRICES : "has"
    COMMODITIES ||--o{ MARKET_PRICES : "priced in"
    COMMODITIES ||--o{ PRODUCE_LISTINGS : "of"
    COMMODITIES ||--o{ BUYER_REQUIREMENTS : "of"
    FARMERS ||--o{ PRODUCE_LISTINGS : "lists"
    BUYERS ||--o{ BUYER_REQUIREMENTS : "posts"
    BUYER_REQUIREMENTS ||--o{ MATCHES : "generates"
    PRODUCE_LISTINGS ||--o{ MATCHES : "matched in"
    BUYER_REQUIREMENTS ||--o{ AGGREGATIONS : "generates"
    AGGREGATIONS ||--o{ AGGREGATION_MEMBERS : "includes"
    PRODUCE_LISTINGS ||--o{ AGGREGATION_MEMBERS : "contributes"
    FARMERS ||--o{ AGGREGATION_MEMBERS : "contributes"
    MATCHES ||--o| TRANSACTIONS : "becomes"
    AGGREGATIONS ||--o| TRANSACTIONS : "becomes"
    TRANSACTIONS ||--o{ TRANSACTION_ITEMS : "itemised as"
    FARMERS ||--o{ TRANSACTION_ITEMS : "paid for"
    USERS ||--o{ NOTIFICATIONS : "receives"
```

---

## 1. Farmer Registration & Produce Listing

A farmer joins the platform, gets verified, and declares available or expected produce that becomes visible supply for matching.

```mermaid
flowchart TD
    A([Farmer opens app / USSD]) --> B[Register: name, phone, district, village]
    B --> C{Phone number verified?}
    C -- No --> D[Send OTP via SMS] --> C
    C -- Yes --> E[Account created — role: farmer]
    E --> F[Market officer / admin reviews profile]
    F --> G{Verified?}
    G -- No --> H[Flag for follow-up, limited access]
    G -- Yes --> I[Farmer creates produce listing:\ncommodity, quantity, harvest/availability date, asking price]
    I --> J[Listing status = available]
    J --> K[Listing becomes visible to matching engine]
    K --> L([End])
```

---

## 2. Buyer Registration & Requirement Posting

A buyer registers, then posts a commodity requirement that immediately triggers a search for matching supply.

```mermaid
flowchart TD
    A([Buyer registers]) --> B[Business name, buyer type, district]
    B --> C[Account created — role: buyer]
    C --> D[Buyer posts requirement:\ncommodity, quantity, offered price,\ncollection market, deadline]
    D --> E[Requirement status = open]
    E --> F[Trigger matching engine]
    F --> G([Continue to Matching workflow])
```

---

## 3. Automated Matching (Direct)

The rule-based engine looks for a single farmer listing that can satisfy a requirement on its own before considering aggregation.

```mermaid
flowchart TD
    A([New / open requirement]) --> B[Query available listings:\nsame commodity, quantity >= required,\nwithin acceptable distance of collection market]
    B --> C{Any single listing\nfully covers requirement?}
    C -- Yes --> D[Create match: type = direct]
    D --> E[Notify farmer and buyer]
    E --> F{Both confirm?}
    F -- Yes --> G[Match status = confirmed_both]
    G --> H[Create transaction]
    H --> I[Update listing + requirement status]
    F -- No / timeout --> J[Match status = rejected / expired]
    J --> K([Fall through to Aggregation workflow])
    C -- No --> K
```

---

## 4. Produce Aggregation

When no single farmer can meet a requirement, the engine combines compatible quantities from nearby farmers into one proposed supply group.

```mermaid
flowchart TD
    A([No direct match found]) --> B[Query all available listings:\nsame commodity, same/nearby district,\nstatus = available or partially_reserved]
    B --> C[Rank listings — e.g. proximity to\ncollection market, then freshness]
    C --> D[Greedily sum quantities\nuntil target quantity reached]
    D --> E{Combined quantity\n>= required quantity?}
    E -- No --> F[No aggregation possible —\nnotify buyer, keep requirement open]
    E -- Yes --> G[Create aggregation group\nstatus = proposed]
    G --> H[Create aggregation_members\nfor each contributing listing]
    H --> I[Notify each contributing farmer]
    I --> J{Farmer accepts\ncontribution?}
    J -- Decline --> K[Remove member, re-run selection\nfor the shortfall]
    K --> D
    J -- Accept --> L[Member status = accepted]
    L --> M{All members accepted\nand total still >= required?}
    M -- Yes --> N[Aggregation status = confirmed]
    N --> O[Notify buyer: combined supply ready]
    O --> P[Buyer confirms] --> Q[Create transaction +\ntransaction_items per farmer]
```

---

## 5. Market Price Comparison & Recommendation

Helps a farmer decide where to sell by weighing price against estimated transport cost, not price alone.

```mermaid
flowchart TD
    A([Farmer requests market recommendation\nfor a commodity]) --> B[Fetch latest prices for that\ncommodity across active markets]
    B --> C[Fetch / estimate transport cost\nfrom farmer's district to each market]
    C --> D[Compute net value =\nprice_per_unit − transport_cost_per_unit]
    D --> E[Rank markets by net value]
    E --> F[Return top 3 recommended markets]
    F --> G[Farmer selects market\nor adjusts listing accordingly]
```

---

## 6. Notifications & Alerts

A single dispatch path so any event in the system (match, aggregation invite, price change) reaches the right user on the right channel.

```mermaid
flowchart TD
    A([Triggering event:\nmatch proposed / aggregation invite /\nprice alert / transaction update]) --> B[Create notification record\nstatus = pending]
    B --> C{User has smartphone /\ndata access?}
    C -- Yes --> D[Deliver in-app]
    C -- No --> E[Send via SMS gateway]
    D --> F[Mark status = sent]
    E --> F
    F --> G{Delivery confirmed?}
    G -- No --> H[Retry / escalate to alternate channel]
    G -- Yes --> I([Done])
```
