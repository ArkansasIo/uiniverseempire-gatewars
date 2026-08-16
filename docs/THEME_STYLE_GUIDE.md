# Universe Civilization: Empire at Wars

## Theme and Style Guide

This document defines the visual language for the game interface. The intended
style is a dark sci-fi military command center inspired by space empires,
Stargate operations, fleet control, research networks, and planetary colonies.

## 1. Visual Identity

The interface should feel like a futuristic strategic operations system:

- Dark space-command atmosphere
- Blue and cyan illuminated interface accents
- Military and imperial information hierarchy
- Technical dashboards and tactical data panels
- Stargate, fleet, orbital, research, and colony imagery
- Clear separation between navigation, resources, and active content
- Modern polish layered over a classic browser strategy-game structure

The overall impression is:

> A high-security interstellar command console used to manage an expanding
> empire.

## 2. Color Palette

### Core Colors

| Purpose | Color | Hex |
|---|---|---|
| Black space background | Black | `#000000` |
| Deep space background | Space navy | `#030B16` |
| Main blue background | Dark navy | `#07121E` |
| Panel background | Navy panel | `#09172A` |
| Strong panel surface | Blue-black | `#0B1E34` |
| Teal surface | Dark teal | `#061C1C` |
| Main text | Ice white | `#F0FFFF` |
| Blue-theme text | Pale blue-white | `#EBF6FF` |
| Muted text | Steel blue | `#9DC2FF` |

### Accent Colors

| Purpose | Color | Hex |
|---|---|---|
| Primary blue accent | Interface blue | `#4DA3FF` |
| Strong blue accent | Action blue | `#1D66BF` |
| Stargate accent | Stargate blue | `#6FB8FF` |
| Cyan signal light | Signal cyan | `#62DDFF` |
| OG theme accent | Teal cyan | `#5AD0D0` |
| Teal border | Interface teal | `#2B6363` |
| Bright focus border | Light cyan | `#72D9FF` |

### Status Colors

| Status | Color | Hex |
|---|---|---|
| Success | Green | `#46C982` |
| Warning | Gold | `#E8B64C` |
| Danger | Red | `#E05D5D` |
| Information | Blue | `#4DA3FF` |

## 3. Theme Variants

The game supports four selectable themes. The body class should be applied as
`theme-<name>`.

### OG Theme

- Background: `#000000`
- Panels: dark teal and black
- Accent: `#5AD0D0`
- Borders: `#2B6363`
- Text: `#F0FFFF`
- Mood: classic Stargate Wars / original strategy-game interface

### White Theme

- Background: `#F4F6F8`
- Panels: white and pale gray
- Accent: `#1788B3`
- Borders: `#D7E0E8`
- Text: `#1F2B3A`
- Mood: clean, bright, and administrative

### Blue Theme

- Background: `#07121E`
- Panels: `#0B1E34`
- Accent: `#4DA3FF`
- Strong accent: `#1D66BF`
- Borders: `#315F9F`
- Text: `#EBF6FF`
- Mood: deep-space fleet command

### Stargate Theme

- Background: `#030B16`
- Panels: `#09172A`
- Accent: `#6FB8FF`
- Strong accent: `#2F6FAE`
- Borders: `#3B7DB6`
- Text: `#F2F7FF`
- Mood: Stargate portal technology and advanced command systems

## 4. Typography

### Primary Font

Use a modern sans-serif stack for the application shell:

```css
font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
```

### Console Font

Use monospace text only for terminals, logs, command output, and technical
readouts:

```css
font-family: "Consolas", "Courier New", monospace;
```

### Heading Rules

- Use uppercase headings for command-center sections
- Use strong weight and modest letter spacing
- Do not underline headings
- Use cyan or blue accents for labels and section markers
- Keep headings compact and information-dense

Example:

```css
font-size: 13px;
font-weight: 700;
letter-spacing: 0.12em;
text-transform: uppercase;
```

## 5. Layout Structure

The primary game shell consists of:

1. Top header with branding and live resource statistics
2. Secondary header with search, quick links, and settings
3. Left navigation containing expandable operation sections
4. Main content panel for the active game page
5. Footer and system information area

The desktop layout uses a wide command-center shell with a left navigation
column and a larger content area. On screens below approximately `900px`, the
navigation becomes stacked above the content.

Recommended desktop proportions:

- Shell width: up to `1540px`
- Sidebar width: approximately `310px`
- Content area: flexible remaining width
- Page spacing: `14px` to `24px`

## 6. Panels and Cards

Panels should use layered dark gradients rather than flat black wherever the
dark themes are active.

Recommended properties:

```css
background: linear-gradient(145deg, rgba(6, 25, 45, 0.93), rgba(4, 15, 30, 0.91));
border: 1px solid rgba(81, 170, 226, 0.34);
border-radius: 7px;
box-shadow: inset 0 1px rgba(185, 231, 255, 0.05);
```

Cards should:

- Use a subtle border and soft inner highlight
- Have rounded corners between `6px` and `12px`
- Gain a brighter border on hover
- Keep enough spacing for data-heavy content to remain readable

## 7. Navigation

The left navigation is an operations directory, not a decorative menu.

- Group links into expandable sections
- Use uppercase section labels
- Use small icons from `images/ui/`
- Use cyan or blue borders for active and hover states
- Keep navigation dense but readable
- Use chevrons or disclosure markers for expandable groups

Suggested interaction styling:

```css
background: linear-gradient(90deg, rgba(15, 86, 130, 0.75), rgba(7, 40, 70, 0.6));
border-color: rgba(119, 210, 255, 0.58);
```

## 8. Buttons and Controls

Buttons represent commands, upgrades, deployments, trades, research actions,
and other strategic decisions.

- Use solid borders instead of dotted legacy borders
- Use `6px` rounded corners
- Use blue or teal gradient fills for primary actions
- Use uppercase labels where the action is important
- Provide hover, focus, and disabled states
- Use visible accent focus rings for keyboard navigation

Primary action example:

```css
background: linear-gradient(90deg, #0B5C91, #1685BE);
border: 1px solid #79DCFF;
color: #F5FDFF;
border-radius: 6px;
```

## 9. Data Presentation

Tables, resource displays, research trees, and battle reports should feel like
mission-control readouts.

- Use compact rows and clear column alignment
- Use uppercase, accent-tinted table headers
- Use zebra striping for long tables
- Highlight rows on hover
- Use green, gold, and red only for meaningful status information
- Avoid excessive decoration around frequently scanned data

Resource statistics should appear as compact stat pills in the top header. Each
pill should show a short label and a prominent value.

## 10. Imagery and Icons

Use the existing visual assets instead of introducing unrelated icon systems.

Important image categories include:

- Empire command backdrop
- Stargate portal imagery
- Fleet taskforce art
- Colony world art
- Orbital research art
- Galaxy backgrounds
- Inline SVG navigation and feature icons

Icons are located under `images/ui/`. Use them for sections such as:

- Empire
- Operations
- Military
- Research
- Economy
- Diplomacy
- Intelligence
- Universe
- Governance

Images should normally be used as background art with a dark overlay so text
remains readable.

## 11. Title and Login Screen

The public title screen uses a dark command-console treatment regardless of the
selected in-game theme.

- Use the empire command backdrop as the background
- Apply a dark blue overlay
- Center the authentication card
- Use a glass-like dark card with a cyan border
- Use uppercase labels and a glowing signal indicator
- Use blue gradient buttons for login and registration

The login screen should communicate secure access to an active military network.

## 12. Motion and Interaction

Motion should be subtle and functional.

- Use short transitions around `0.12s` to `0.16s`
- Animate hover color, border brightness, shadow, and small movement
- Avoid large page animations or distracting effects
- Use glow effects sparingly for active signals and focus states

## 13. Accessibility and Responsiveness

- Maintain readable contrast between text and panels
- Keep focus outlines visible
- Do not rely on color alone to communicate status
- Ensure tables and cards can fit narrow screens
- Stack the sidebar above content below `900px`
- Reduce panel padding on mobile
- Keep login and action controls large enough for touch use

## 14. Design Rules

### Do

- Use dark navy, blue, cyan, and teal as the main visual language
- Keep layouts structured like a command console
- Use existing SVG icons and game artwork
- Prefer clear information hierarchy over decoration
- Use accent variables so all themes remain consistent
- Preserve responsive behavior on smaller screens

### Do Not

- Introduce unrelated bright colors as primary branding
- Use playful, cartoon-like styling
- Use excessive gradients or glowing effects
- Mix unrelated font families throughout the interface
- Remove status colors from combat, economy, or system feedback
- Replace the command-center layout with generic marketing cards

## 15. Design Summary

The Universe Civilization: Empire at Wars theme is a **dark futuristic space
strategy interface**. Its signature combination is:

```text
Deep navy space backgrounds
+
Blue and cyan illuminated controls
+
Military command-console typography
+
Empire, Stargate, fleet, colony, and research imagery
+
Dense strategic data presented in modern rounded panels
```

Every new screen should look like it belongs inside the same interstellar
operations network.
