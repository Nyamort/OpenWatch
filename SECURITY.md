# Security Policy

## Supported versions

Only the latest release of Nightwatch receives security fixes.

| Version | Supported |
|---------|-----------|
| Latest  | ✅ |
| Older   | ❌ |

## Reporting a vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability, send an email to **security@example.com** (replace with your actual address) with the subject line:

```
[SECURITY] Brief description of the issue
```

Include as much detail as possible:

- A description of the vulnerability and its potential impact
- Steps to reproduce or a proof-of-concept
- Any relevant environment details (PHP version, OS, etc.)

You should receive an acknowledgement within **48 hours**. We aim to release a patch within **14 days** for critical issues.

We ask that you:

- Give us a reasonable amount of time to address the issue before any public disclosure
- Avoid accessing or modifying data that does not belong to you
- Act in good faith

We are happy to credit you in the release notes if you wish.

## Scope

The following are considered in scope:

- Authentication and authorization bypasses
- Remote code execution
- SQL injection or data exfiltration
- Cross-site scripting (XSS) in the dashboard
- Insecure direct object references (IDOR)
- Exposure of ingestion tokens or session credentials

The following are **out of scope**:

- Denial of service attacks
- Issues requiring physical access to the server
- Vulnerabilities in third-party dependencies (please report those upstream)
- Social engineering attacks
