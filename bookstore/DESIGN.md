# Design System: Apex Performance System
**Project Title:** Apex Fitness & Performance Center  
**Stitch Project ID:** `6697286312568266889`  
**Stitch Screen ID:** `a3307e60ed3c44aebd698ef7d9b6dc21`  
**Synthesized Via:** Google Stitch MCP + Stitch Skills (`design-md`, `enhance-prompt`, `taste-design`)

---

## 1. Brand Philosophy & Atmosphere

The **Apex Performance System** channels an aggressive, elite, high-octane athletic aesthetic engineered for peak physical output, biometric precision, and zero digital friction. Inspired by the disciplined architectural luxury of Equinox, the kinetic digital edge of Nike Training Club, and the raw high-energy culture of Gymshark, the interface projects power, discipline, and performance.

### Aesthetic Dimensions
- **Dark Kinetic Mode (Default):** Immersive, obsidian-toned surfaces evoke low-lit, high-focus performance barbell zones and HIIT studios.
- **Electric Biometrics:** Striking neon accents burst against deep dark substrates, guiding instant focus to training telemetry, live workout countdowns, and primary calls-to-action.
- **Architectural Glass Edge:** Layered glassmorphic panels (`backdrop-filter: blur(14px)`) paired with whisper-thin luminous borders mimic high-precision digital gym consoles and titanium weight racks.
- **Dual-State Adaptation:** Fully coupled with the server-side persistent `apex_theme` cookie (30-day lifespan), seamlessly switching between **Dark Gym Mode** and **Light Athletic Mode** without Flash of Unstyled Content (FOUC).

---

## 2. Color Palette & Functional Roles

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       APEX PERFORMANCE COLOR TOKENS                         │
├───────────────────────┬───────────┬─────────────────────────────────────────┤
│ Token Name            │ Hex Code  │ Functional Role & Semantic Context      │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Deep Obsidian Canvas  │ #090D16   │ Root dark mode background canvas        │
│ Midnight Charcoal     │ #111827   │ Elevated glass surfaces, cards, modals   │
│ Charcoal Highlight    │ #1C1F29   │ Inner card containers & telemetry slots  │
│ Surface Border Light  │ rgba(255) │ 1px solid rgba(255, 255, 255, 0.08)     │
│ Surface Border Glow   │ rgba(255) │ Hover state: rgba(255, 255, 255, 0.22)  │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Electric Crimson      │ #F43F5E   │ Primary CTA buttons, intensity badges   │
│ Primary Crimson Glow  │ rgba(244) │ 0 0 24px -4px rgba(244, 63, 94, 0.40)   │
│ Volt Orange           │ #FF5500   │ Secondary accents, burn streaks, highlights│
│ Emerald Biometrics    │ #10B981   │ Live active state, verified staff chips │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Pure Ice White        │ #F8FAFC   │ Primary high-contrast text and numbers  │
│ Muted Slate Gray      │ #94A3B8   │ Secondary captions, metadata, subtitles │
├───────────────────────┼───────────┼─────────────────────────────────────────┤
│ Light Athletic Canvas │ #F8FAFC   │ Root light mode background canvas       │
│ Light Surface Card    │ #FFFFFF   │ Pure white surface with multi-tier glow │
│ Light Slate Text      │ #0F172A   │ High-legibility text for light mode     │
└───────────────────────┴───────────┴─────────────────────────────────────────┘
```

---

## 3. Typography Scale & Hierarchy

The typography pairs authoritative, uppercase athletic geometric display headers (`Space Grotesk` / `Outfit`) with hyper-legible neutral body text (`Plus Jakarta Sans` / `Inter`).

### Hierarchy Specifications

| Level | Font Family | Size / Line-Height | Weight | Letter Spacing | Case |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Display Hero** | Space Grotesk / Outfit | 64px / 68px | 700 (Bold) | +0.08em | UPPERCASE |
| **Headline XL** | Space Grotesk / Outfit | 44px / 50px | 700 (Bold) | +0.06em | UPPERCASE |
| **Headline LG** | Space Grotesk / Outfit | 32px / 38px | 600 (SemiBold)| +0.05em | UPPERCASE |
| **Headline MD** | Space Grotesk / Outfit | 24px / 30px | 600 (SemiBold)| +0.04em | UPPERCASE |
| **Headline SM** | Space Grotesk / Outfit | 18px / 24px | 600 (SemiBold)| +0.03em | UPPERCASE |
| **Body LG** | Plus Jakarta Sans / Inter | 18px / 28px | 400 (Regular) | -0.01em | Normal |
| **Body MD** | Plus Jakarta Sans / Inter | 15px / 22px | 400 (Regular) | 0.00em | Normal |
| **Body SM** | Plus Jakarta Sans / Inter | 13px / 18px | 400 (Regular) | 0.00em | Normal |
| **Label LG** | Space Grotesk / Outfit | 14px / 18px | 600 (SemiBold)| +0.08em | UPPERCASE |
| **Label MD** | Space Grotesk / Outfit | 12px / 16px | 600 (SemiBold)| +0.10em | UPPERCASE |
| **Label SM / Badges**| Space Grotesk / Outfit | 10px / 14px | 700 (Bold) | +0.12em | UPPERCASE |

---

## 4. Geometry, Elevation & Motion

### Geometry (Corner Radii)
- **Tag / Micro Badge:** `4px` (`rounded-sm`)
- **Inputs & Button CTAs:** `8px` (`rounded-md`)
- **Cards & Bento Panels:** `14px` (`rounded-xl`)
- **Avatars & Pill Badges:** `9999px` (`rounded-pill`)

### Depth & Glow Layers (Photonic Depth)
1. **Base Ground:** Pure `#090D16` (Obsidian) in Dark Mode, `#F8FAFC` in Light Mode.
2. **Glassmorphic Substrate (`.glass-panel`):**
   - Background: `rgba(17, 24, 39, 0.78)` (Dark) / `rgba(255, 255, 255, 0.85)` (Light)
   - Backdrop Filter: `blur(14px)`
   - Border: `1px solid rgba(255, 255, 255, 0.08)` (Dark) / `1px solid rgba(15, 23, 42, 0.08)` (Light)
3. **Interactive Lift (`.glass-panel-interactive`):**
   - Hover Transform: `translateY(-4px)`
   - Hover Border: `rgba(244, 63, 94, 0.40)`
   - Hover Glow: `0 12px 32px -4px rgba(244, 63, 94, 0.25)`

### Kinetic Motion
- **Micro-transitions:** `all 0.22s cubic-bezier(0.16, 1, 0.3, 1)` for button hovers and border glows.
- **Pill Pulse Animation:** `@keyframes pulse-glow` creating energetic breathing indicator on live workout status and active session badges.

---

## 5. CSS Custom Properties (:root Variables)

```css
:root {
  /* Brand Accents */
  --apex-crimson: #F43F5E;
  --apex-crimson-hover: #E11D48;
  --apex-volt: #FF5500;
  --apex-emerald: #10B981;
  
  /* Light Athletic Mode */
  --apex-bg: #F8FAFC;
  --apex-surface: #FFFFFF;
  --apex-surface-subtle: #F1F5F9;
  --apex-border: rgba(15, 23, 42, 0.08);
  --apex-border-hover: rgba(244, 63, 94, 0.30);
  --apex-text-primary: #0F172A;
  --apex-text-muted: #64748B;
  --apex-glass-bg: rgba(255, 255, 255, 0.85);
  --apex-card-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06);
  --apex-glow: 0 10px 25px -3px rgba(244, 63, 94, 0.18);
}

[data-bs-theme="dark"], .theme-dark {
  /* Dark Gym Mode */
  --apex-bg: #090D16;
  --apex-surface: #111827;
  --apex-surface-subtle: #1C1F29;
  --apex-border: rgba(255, 255, 255, 0.08);
  --apex-border-hover: rgba(255, 255, 255, 0.22);
  --apex-text-primary: #F8FAFC;
  --apex-text-muted: #94A3B8;
  --apex-glass-bg: rgba(17, 24, 39, 0.80);
  --apex-card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.50);
  --apex-glow: 0 0 24px -4px rgba(244, 63, 94, 0.35);
}
```

---

## 6. Integration Across Application Layers

1. **Header Navigation ([`components/header.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/components/header.php)):**
   - Frosted glass navbar (`backdrop-filter: blur(14px)`) sticky on scroll.
   - Distinctive VIP Member status chip (Admin: Midnight Gold, Trainer: Cobalt Blue, Member: Crimson Red).
   - Theme switch pill displaying active cookie state (30-day persistent).
2. **Homepage Hero & Catalog ([`public/index.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/public/index.php)):**
   - High-impact athletic hero with performance stat counter strip (1,200+ Athletes, 15+ Coaches, 24+ Programs).
   - Personalized "Welcome Back" cookie banner with instantaneous resume link.
   - Interactive class cards with gradient overlay, coach tag, schedule chips, and price badge.
3. **Training Classes Hub ([`pages/classes.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/pages/classes.php)):**
   - Athletic discipline category pill filters that set `apex_fav_category` cookie on click.
   - Staff action bar for Admin/Trainer with sleek hover-elevated controls.
4. **Member Dashboard & Telemetry Inspector ([`pages/dashboard.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/pages/dashboard.php)):**
   - Bento Grid layout organizing metrics, profile credentials, and class directory.
   - High-tech **"Client-Side Cookies & Session Inspector"** table rendering real-time inspection data with clear action triggers.
