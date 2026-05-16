# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Project Overview

LitraDesa is a **hybrid physical-digital village library management system** for rural Indonesia. This is NOT a typical digital library - physical books remain primary, with technology reducing friction rather than replacing the physical experience.

## Critical Non-Obvious Concepts

### 1. QR-Centric Workflow (PRIMARY Interaction Model)
- **Member cards ARE QR codes** (not traditional cards with QR stickers)
- **Every physical book has a unique QR code**
- **Core transaction flow**: Scan member QR + Scan book QR = Loan/Return
- This is the PRIMARY way users interact with the system
- Target: <10 second transaction time (critical performance requirement)

### 2. Dual Book Types (Different Workflows)
- **Hardbooks**: Physical books with QR codes (scan-based workflow)
- **Softbooks**: Digital PDF/EPUB files (download-based workflow)
- Same book can exist in BOTH formats simultaneously
- Each type requires different handling in code (inventory, transactions, access control)

### 3. Village-Specific Constraints
- **Target users**: Rural Indonesian village residents with low-tech literacy
- **Mobile-first**: 90% mobile usage expected
- **Offline capability essential**: Unreliable internet connectivity
- **Local deployment**: Docker-based local server (not cloud-only)
- **Physical location**: Village hall as pickup/return point

### 4. Indonesian Context
- **KTP**: Indonesian national ID card (OCR scanning for registration)
- **WhatsApp integration**: Primary communication channel in Indonesia
- **Admin approval required**: New members need approval (not instant signup)
- **24-hour reservation window**: Suggests high demand/limited inventory

### 5. Multi-Village SaaS Vision
- Current implementation: Single village
- Architecture should consider: Future multi-tenant scalability
- Plan for: Phased rollout across multiple villages

## Tech Stack (Planning Phase)

- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Next.js 14 OR React with Inertia.js (decision pending)
- **Database**: PostgreSQL
- **Real-time**: Laravel Reverb
- **Deployment**: Docker
- **PWA**: For offline capability

## Project Status

**PLANNING PHASE** - Only PRD exists, no code implementation yet.

## Key References

- See `docs/PRD.md` for complete product requirements
- Focus on physical-first philosophy when making design decisions
- Prioritize simplicity and speed for low-tech literacy users