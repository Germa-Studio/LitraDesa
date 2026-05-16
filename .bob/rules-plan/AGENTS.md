# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Plan Mode Specific Rules

When creating plans for LitraDesa, prioritize these non-obvious aspects:

### Performance-First Planning
- **<10 second transaction time** is the PRIMARY success metric
- Every feature must be evaluated against this requirement
- QR scanning workflow must be optimized for speed
- Database queries, API calls, UI rendering all impact this metric
- Plan for performance testing and optimization from day one

### Multi-Village Scalability Strategy
- **Current scope**: Single village implementation
- **Future vision**: Multi-village SaaS platform
- **Planning approach**: Build single-tenant with multi-tenant patterns
  - Tenant-aware database schema (even for single tenant)
  - API design that supports tenant context
  - Avoid hardcoded village-specific logic
  - Plan for data isolation and security
- **Phased rollout strategy**:
  - Phase 1: Single village pilot (current)
  - Phase 2: 2-3 villages (validate multi-tenant architecture)
  - Phase 3: Regional expansion (10+ villages)

### Offline-First Architecture
- **Critical constraint**: System must work without internet
- **Planning implications**:
  - Service worker strategy for PWA
  - Local data caching strategy
  - Transaction queue and sync mechanism
  - Conflict resolution strategy (e.g., same book loaned offline by two admins)
  - Offline-capable QR scanning
- Plan for graceful degradation when offline

### Dual Book Type System Planning
- **Hardbooks** and **Softbooks** require different workflows:
  - Hardbooks: Physical inventory, QR-based transactions, location tracking
  - Softbooks: Digital downloads, DRM-free access, offline storage
- **Planning considerations**:
  - Separate but related data models
  - Different transaction flows
  - Unified search/discovery experience
  - Same book can exist in both formats
- Plan for future book type additions (e.g., audiobooks)

### Indonesian Context Planning
- **KTP OCR**: Plan for Indonesian ID card text extraction
- **WhatsApp API**: Primary notification channel (not email/SMS)
- **Bahasa Indonesia**: UI/UX must support Indonesian language
- **Low bandwidth**: Optimize for slow/unreliable connections
- **Mobile-first**: 90% mobile usage expected
- **Local deployment**: Docker-based server at village hall

### Technology Stack Decisions
- **Backend**: Laravel 11 (PHP 8.3) - chosen
- **Frontend**: Next.js 14 OR React with Inertia.js - **decision pending**
  - Consider offline capability implications
  - Evaluate SSR vs CSR for performance
  - Plan for PWA requirements
- **Database**: PostgreSQL - chosen for robust transactions
- **Real-time**: Laravel Reverb - for book availability updates
- **Deployment**: Docker - for easy village-level installation

### Critical Success Factors
- **<10 second transaction time**: Non-negotiable
- **Offline capability**: Must work without internet
- **Low-tech literacy**: Simple, intuitive UI
- **QR-centric workflow**: Primary interaction model
- **24-hour reservation window**: High demand/limited inventory
- **Admin approval**: New members need approval

### Phased Implementation Strategy
1. **Phase 1: Core QR Workflow**
   - Member registration (KTP OCR)
   - Book catalog (hardbooks only)
   - QR-based loan/return
   - Basic admin dashboard
2. **Phase 2: Digital Books**
   - Softbook catalog
   - Download management
   - Dual-type book support
3. **Phase 3: Advanced Features**
   - Reservations
   - WhatsApp notifications
   - Real-time availability
   - Analytics dashboard
4. **Phase 4: Multi-Village**
   - Multi-tenant architecture
   - Village-level admin
   - Cross-village reporting

## Key References
- See `/docs/PRD.md` for complete requirements
- Main `/AGENTS.md` for project overview