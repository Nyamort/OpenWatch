# Technical Specifications - Log Detail Analytics

## 1. Data model
- Retrieve exact log event from scope and provide structured view payload (`context`, `extra`, exception details).

## 2. Rendering rules
- Build JSON tree component with expandable nodes.
- Apply redaction transformation before response render.
- Show exception preview blocks and stack traces as plain text unless linking is enabled.
