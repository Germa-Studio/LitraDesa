# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Ask Mode Specific Rules

When answering questions about LitraDesa, emphasize these non-obvious aspects:

### Physical-First Philosophy
- This is NOT a typical digital library system
- Physical books remain the primary focus
- Technology reduces friction, doesn't replace physical experience
- QR codes bridge physical and digital worlds
- Village hall is the central physical location

### QR-Centric Interaction Model
- **Member cards ARE QR codes** (not cards with QR stickers)
- **Every physical book has a unique QR code**
- **Primary workflow**: Scan member QR + Scan book QR = Loan/Return
- Target transaction time: <10 seconds (critical requirement)
- This is fundamentally different from traditional library systems

### Indonesian Village Context
- **Target users**: Rural Indonesian residents with low-tech literacy
- **KTP**: Indonesian national ID card (used for registration with OCR)
- **WhatsApp**: Primary communication channel in Indonesia (not email/SMS)
- **Offline-first**: Unreliable internet connectivity is the norm
- **Mobile-first**: 90% mobile usage expected
- **Local deployment**: Docker-based server at village hall (not cloud-only)

### Dual Book Type System
- **Hardbooks**: Physical books with QR codes (scan-based workflow)
- **Softbooks**: Digital PDF/EPUB files (download-based workflow)
- Same book can exist in BOTH formats simultaneously
- Each type has completely different user workflows
- Not just "physical vs digital" - different transaction models

### Critical Design Constraints
- **<10 second transaction time**: Non-negotiable performance requirement
- **24-hour reservation window**: Indicates high demand/limited inventory
- **Admin approval required**: New members need approval (not instant signup)
- **Offline capability**: System must work without internet
- **Low bandwidth**: Optimize for slow/unreliable connections

### Multi-Village Vision
- Current scope: Single village implementation
- Future vision: Multi-village SaaS platform
- Architecture should consider future scalability
- Phased rollout strategy across multiple villages

### Tech Stack Context
- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Next.js 14 OR React with Inertia.js (decision pending)
- **Database**: PostgreSQL
- **Real-time**: Laravel Reverb
- **Deployment**: Docker
- **PWA**: For offline capability

## When Explaining Features

- Always contextualize within the physical-first philosophy
- Emphasize the QR-centric workflow as the primary interaction
- Consider low-tech literacy when explaining UI/UX
- Reference Indonesian context (KTP, WhatsApp, village hall)
- Highlight offline-first requirements
- Explain dual book type implications

## Key References
- See `/docs/PRD.md` for complete requirements
- Main `/AGENTS.md` for project overview