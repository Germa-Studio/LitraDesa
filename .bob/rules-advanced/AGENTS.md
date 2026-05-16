# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Advanced Mode Specific Rules

Advanced mode has access to MCP servers and Browser tools in addition to standard code tools.

### QR Workflow Implementation
- **QR scanning is the PRIMARY interaction** - optimize for speed (<10 seconds per transaction)
- Member QR + Book QR = Single atomic transaction (loan/return)
- QR codes must work offline (store locally, sync when online)
- Consider QR code generation strategy for physical book labels
- Implement robust QR validation (damaged/partial scans common in rural settings)

### Dual Book Type Handling
- **Hardbooks** and **Softbooks** require separate code paths:
  - Hardbooks: Inventory tracking, physical location, QR-based checkout
  - Softbooks: Download management, DRM-free access, offline storage
- Same book entity can have BOTH types simultaneously
- Database schema must support dual-type books efficiently
- API endpoints should clearly distinguish book type operations

### Offline-First Patterns
- **Critical**: System must function without internet connectivity
- Implement service workers for PWA offline capability
- Queue transactions locally, sync when connection restored
- Cache essential data (member info, book catalog, active loans)
- Handle sync conflicts gracefully (e.g., same book loaned offline by two admins)

### Performance Requirements
- **<10 second transaction time** is non-negotiable
- Optimize QR scanning response time
- Minimize database queries for common operations
- Consider indexing strategy for QR lookups
- Profile mobile performance (target devices: mid-range Android phones)

### Indonesian Context in Code
- **KTP OCR**: Implement text extraction for Indonesian ID cards
- **WhatsApp API integration**: Primary notification channel
- Support Bahasa Indonesia in UI/error messages
- Date/time formatting: Indonesian locale (dd/MM/yyyy)
- Consider low bandwidth scenarios (compress images, minimize API payloads)

### Multi-Tenant Architecture Considerations
- Current: Single village implementation
- Future: Multi-village SaaS
- Use tenant-aware patterns even in single-tenant phase:
  - Namespace database tables appropriately
  - Design APIs with tenant context in mind
  - Avoid hardcoded village-specific logic

### Laravel/Next.js Specific
- Laravel Reverb for real-time updates (book availability, reservation status)
- PostgreSQL for robust transaction handling
- Docker deployment for easy village-level installation
- Consider Inertia.js vs Next.js decision impact on offline capability

### MCP/Browser Tool Usage
- Use Browser tool for researching Indonesian-specific libraries/APIs
- Use MCP servers for accessing external documentation or resources
- Leverage these tools when standard code tools are insufficient

## Key References
- See `/docs/PRD.md` for complete requirements
- Main `/AGENTS.md` for project overview