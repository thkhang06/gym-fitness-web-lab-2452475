# Design System: Tiger Performance Architecture
**Project Title:** TIGER FITNESS Center  
**Brand Mascot:** 2D Geometric Tiger Emblem (`tiger-logo.svg`, `favicon.svg`)  
**Stitch Project ID:** `6697286312568266889`  
**Stitch Screen ID:** `a3307e60ed3c44aebd698ef7d9b6dc21`  
**Synthesized Via:** Google Stitch MCP + Stitch Skills (`design-md`, `enhance-prompt`, `taste-design`)

---

## 1. Brand Philosophy & Atmosphere

The **Tiger Performance Architecture** channels an aggressive, elite, predatory athletic aesthetic engineered for peak physical output, biometric precision, and modern competitive drive. Inspired by high-octane performance brands (Nike Training Club, Gymshark, Predator Esport aesthetics), the interface combines the raw power of a predatory tiger with high-precision digital telemetry.

### Aesthetic Dimensions
- **Dark Tiger Mode (Default):** Deep obsidian-black surfaces (`#0A0B0E`) and frosted glass cards (`#12151E`) evoke low-lit, high-focus performance barbell zones and combat rings.
- **Predatory Tiger Energy:** Striking **Tiger Orange (`#FF6B00`)** and **Amber Gold (`#FF9E00`)** gradients burst against dark substrates, guiding instant focus to training telemetry, live countdowns, and primary calls-to-action.
- **2D Geometric Mascot:** Sharp, symmetrical vector tiger head crest positioned as the brand vanguard in the navigation, hero section, and browser tab favicon.
- **Dual-State Adaptation:** Fully coupled with the server-side persistent `apex_theme` cookie (30-day lifespan), seamlessly switching between **Dark Tiger Gym Mode** and **Light Athletic Ivory Mode** without Flash of Unstyled Content (FOUC).

---

## 2. Color Palette & Functional Roles

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       TIGER FITNESS COLOR TOKENS                            │
├───────────────────────┬───────────┬─────────────────────────────────────────┤
│ Token Name            │ Hex Code  │ Functional Role & Semantic Context      │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Deep Tiger Black      │ #0A0B0E   │ Root dark mode canvas background        │
│ Midnight Obsidian     │ #12151E   │ Elevated glass surfaces, cards, modals   │
│ Charcoal Inset        │ #191D2A   │ Inner card containers & telemetry slots  │
│ Surface Border Light  │ rgba(255) │ 1px solid rgba(255, 255, 255, 0.08)     │
│ Surface Border Hover  │ rgba(255) │ Hover state: rgba(255, 107, 0, 0.45)    │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Primary Tiger Orange  │ #FF6B00   │ Primary CTA buttons, brand badges, focus│
│ Amber Gold Accent     │ #FF9E00   │ Secondary gradients, high-contrast glow │
│ Deep Flame Volt       │ #FF5500   │ Energy bursts, burn indicators          │
│ Tiger Glow Hover      │ rgba(255) │ 0 6px 20px rgba(255, 107, 0, 0.48)      │
│ Emerald Biometrics    │ #10B981   │ Live active state, verified staff chips │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Pure Ice White        │ #F8FAFC   │ Primary high-contrast text and numbers  │
│ Muted Slate Gray      │ #94A3B8   │ Secondary captions, metadata, subtitles │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Light Athletic Ivory  │ #FFFDFB   │ Root light mode background canvas       │
│ Light Surface Card    │ #FFFFFF   │ Pure white surface with multi-tier glow │
│ Charcoal Slate Text   │ #111827   │ High-legibility text for light mode     │
└───────────────────────┴───────────┴─────────────────────────────────────────┘
```

---

## 3. Typography Scale & Hierarchy

The typography pairs authoritative, uppercase athletic display headers (`Space Grotesk`) with hyper-legible neutral body text (`Plus Jakarta Sans`).

| Level | Font Family | Size / Line-Height | Weight | Letter Spacing | Case |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Display Hero** | Space Grotesk | 64px / 68px | 700 (Bold) | +0.08em | UPPERCASE |
| **Headline XL** | Space Grotesk | 44px / 50px | 700 (Bold) | +0.06em | UPPERCASE |
| **Headline LG** | Space Grotesk | 32px / 38px | 600 (SemiBold)| +0.05em | UPPERCASE |
| **Headline MD** | Space Grotesk | 24px / 30px | 600 (SemiBold)| +0.04em | UPPERCASE |
| **Headline SM** | Space Grotesk | 18px / 24px | 600 (SemiBold)| +0.03em | UPPERCASE |
| **Body LG** | Plus Jakarta Sans | 18px / 28px | 400 (Regular) | -0.01em | Normal |
| **Body MD** | Plus Jakarta Sans | 15px / 22px | 400 (Regular) | 0.00em | Normal |
| **Body SM** | Plus Jakarta Sans | 13px / 18px | 400 (Regular) | 0.00em | Normal |
| **Label LG** | Space Grotesk | 14px / 18px | 600 (SemiBold)| +0.08em | UPPERCASE |
| **Label MD** | Space Grotesk | 12px / 16px | 600 (SemiBold)| +0.10em | UPPERCASE |
| **Label SM / Badges**| Space Grotesk | 10px / 14px | 700 (Bold) | +0.12em | UPPERCASE |

---

## 4. Geometry, Elevation & Motion

### Geometry (Corner Radii)
- **Tag / Micro Badge:** `4px` (`rounded-sm`)
- **Inputs & Button CTAs:** `8px` (`rounded-md`)
- **Cards & Bento Panels:** `14px` (`rounded-xl`)
- **Avatars & Pill Badges:** `9999px` (`rounded-pill`)

### Depth & Glow Layers (Photonic Depth)
1. **Base Ground:** Pure `#0A0B0E` (Deep Tiger Black) in Dark Mode, `#FFFDFB` (Athletic Ivory) in Light Mode.
2. **Glassmorphic Substrate (`.glass-panel`):**
   - Background: `rgba(18, 21, 30, 0.82)` (Dark) / `rgba(255, 255, 255, 0.84)` (Light)
   - Backdrop Filter: `blur(14px)`
   - Border: `1px solid rgba(255, 255, 255, 0.08)` (Dark) / `1px solid rgba(17, 24, 39, 0.08)` (Light)
3. **Interactive Lift (`.glass-panel-interactive`):**
   - Hover Transform: `translateY(-4px)`
   - Hover Border: `rgba(255, 107, 0, 0.45)`
   - Hover Glow: `0 12px 32px -4px rgba(255, 107, 0, 0.38)`

---

## 5. CSS Custom Properties (:root Variables)

```css
:root {
  --tiger-orange: #FF6B00;
  --tiger-orange-hover: #FF8533;
  --tiger-amber: #FF9E00;
  --tiger-volt: #FF5500;
  --tiger-emerald: #10B981;
  --apex-bg: #FFFDFB;
  --apex-surface: #FFFFFF;
  --apex-border: rgba(17, 24, 39, 0.08);
  --apex-border-hover: rgba(255, 107, 0, 0.40);
  --apex-text-primary: #111827;
  --apex-text-muted: #6B7280;
  --font-headline: 'Space Grotesk', -apple-system, sans-serif;
  --font-body: 'Plus Jakarta Sans', -apple-system, sans-serif;
}

body.theme-dark, [data-bs-theme="dark"] {
  --apex-bg: #0A0B0E;
  --apex-surface: #12151E;
  --apex-border: rgba(255, 255, 255, 0.08);
  --apex-border-hover: rgba(255, 107, 0, 0.45);
  --apex-text-primary: #F8FAFC;
  --apex-text-muted: #94A3B8;
  --apex-card-shadow: 0 12px 35px -5px rgba(0, 0, 0, 0.70);
  --apex-glow: 0 0 24px -4px rgba(255, 107, 0, 0.38);
}
```
