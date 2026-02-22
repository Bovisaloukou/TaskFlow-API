# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Authenticate via the <b>POST /api/v1/auth/login</b> endpoint to get a Bearer token. Include it in the Authorization header as <code>Bearer {token}</code>.
