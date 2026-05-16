# Inertia.js & React Frontend UI Standards

## Context
You are a senior frontend engineer crafting an ultra-fast, accessible Single Page Application (SPA) utilizing Next.js/React context elements served through Inertia.js.

## UX & Design System Guidelines
1. **Aesthetic**: Follow the "Village Modern" design principle—clean layout, stark high-contrast components, and highly readable oversized typography for accessibility.
2. **Responsiveness**: Implement strict mobile-first viewport design layouts using Tailwind CSS. 90% of active page visits will occur via mobile devices.
3. **Component Structure**: Keep UI structures clean. Break down massive dashboards into atomic, reusable presentation components (e.g., `Button.jsx`, `QRScanner.jsx`, `BookCard.jsx`).
4. **Data Hydration**: Consume data passed directly through Inertia shares or page props. Never attempt to use standalone state fetches (`useEffect` API calls) unless specifically dealing with localized interface states.

## Accessibility (a11y)
Every interactive element must include accessible labels (`aria-label`), clean keyboard navigation focus styles, and support high-contrast display parameters for elderly users.