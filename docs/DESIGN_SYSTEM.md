# Design system

## Direction: "the spec sheet"

The direction is not invented. It is taken from the business's own catalogue and Instagram:

- **Studio photography.** Every product was shot on the same set: a warm amber key light, a stone-grey backdrop and a pale table. The site uses those three tones as its palette.
- **Monospace labels.** The PDF prints "Model: / Product code: / 2850 manat" in a typewriter face. The site keeps that voice for model codes, prices, indices and labels (JetBrains Mono).
- **Ports.** The hardware is defined by its numbered ports (the hEX S and hAP ac³ print "1 2 3 4 5 / LAN / PoE out"). The site uses numbered sections ("01 / Оборудование"), "PORT 1–4" value cards, and a five-port process diagram.

The result reads like a technical specification sheet laid out by an architect. It is calm, precise and product-first; it is not a SaaS template and not a dark "cyber" theme. Only the services band is dark.

## Colour tokens (`resources/css/app.css`, `@theme`)

| Token | Hex | Role |
|---|---|---|
| `ink` | #121314 | text, primary buttons |
| `graphite-900` | #17191b | the dark services section |
| `graphite-950` | #0f1011 | footer |
| `graphite-600/500` | #454a4f / #61676d | secondary text (5.0:1 on paper) |
| `graphite-200` | #d8d9d8 | hairlines, grid gaps |
| `paper` | #f3f0eb | page background (the studio table) |
| `paper-2` | #ebe6df | photo mounts, active pills |
| `amber-500` | #f28a2e | the studio key light: WhatsApp/accent buttons, LEDs |
| `amber-700` | #a9520c | amber **text** on light backgrounds (4.7:1) |
| `signal` | #13b5d8 | "live" LED only (from the SXTsq shoot) |
| `signal-700` | #08708a | focus ring |
| `ok` / `danger` | #23714a / #b23a28 | availability LEDs, form errors |

Checked contrast pairs (WCAG 2.1):

| Pair | Ratio |
|---|---:|
| ink on paper | 16.4 |
| muted text on paper | 5.0 |
| muted text on paper-2 | 4.6 |
| amber-700 on paper | 4.7 |
| ink on amber-500 | 7.5 |
| amber-500 on graphite | 7.1 |
| graphite-300 on graphite | 8.9 |

White on amber is 3.3 and **is not used**; amber buttons carry ink text.

## Typography

| Role | Font | Size | Notes |
|---|---|---|---|
| Display (H1) | Onest 600 | `clamp(2.75rem → 6.25rem)` | tracking −0.035em, line-height 0.98 |
| Title (H2) | Onest 600 | `clamp(2rem → 3.6rem)` | tracking −0.028em |
| Heading (H3) | Onest 600 | `clamp(1.35rem → 1.75rem)` | |
| Lead | Onest 400 | `clamp(1.075rem → 1.3rem)` | line-height 1.55 |
| Body | Onest 400 | 16 px | line-height ~1.6 |
| Label / eyebrow | JetBrains Mono 400 | 12 px | uppercase, +0.12em tracking (**never applied to model names or codes**, which keep their exact case) |
| Prices, codes, phone numbers | JetBrains Mono 500 | | |

Both families cover Cyrillic (Russian) and Latin Extended (Turkmen ä ç ň ö ş ü ý ž). They are downloaded at build time (`vite.config.js`) and served from `/build`; no Google or Bunny requests happen at runtime.

## Layout

- Container: max 85 rem (1360 px). Side padding: 20 px on mobile, 32 px on tablet, 48 px on desktop.
- 12-column grid. Section header pattern: a hairline, then "01 / LABEL" in columns 1–3 and the title in columns 4–12.
- "Hairline grids": product, value and service grids use `gap-px` on a `graphite-200` background, so cells are separated by 1 px rules.
- Section rhythm: 80 px (mobile) to 112 px (desktop) vertical padding, alternating paper and white with one dark band.
- **Text is never laid over photographs.** Captions and spec lines sit beside or below images.

## Components

| Component | File | Notes |
|---|---|---|
| Section head | `components/section-head` | index, label, title, lead, optional action link |
| Product card | `components/product-card` | 4:5 photo (cover or mounted), code label, model, 2-line summary, price, availability LED; the whole card is clickable |
| Price | `components/price` | mono number + localised currency word, or «Цена по запросу» |
| Availability | `components/availability` | LED colour + text (never colour alone) |
| Inquiry form | `components/inquiry-form` | visible labels, hints, inline errors with `aria-describedby`, pill radios for contact method, success panel with WhatsApp follow-up |
| Buttons | `.btn-primary` (ink), `.btn-accent` (amber), `.btn-outline`, `.btn-outline-dark` | min height 48 px (44 px compact) |
| Language switcher | `partials/lang-switcher` | RU / TM / EN, keeps the current page and filters |
| Mobile action bar | `partials/mobile-actions` | fixed Call + WhatsApp below 768 px; respects the iOS safe area |

## Motion

- Scroll reveal: fade plus 14 px rise over 700 ms with a quart ease-out and staggered delays. Applied only when JS is present **and** the user has not asked for reduced motion; otherwise content is static and fully visible.
- Hover: photo scale 1.025–1.03 over 700 ms; colour transitions 200 ms.
- The brand LED next to the wordmark blinks briefly every ~5 s (stepped, not pulsing), and is disabled under reduced motion.

## Imagery rules

1. Product cards and product pages use **only the business's real photographs**. Those photographs are not distributed with this repository; without them a neutral placeholder is shown.
2. Generated illustrations appear only as editorial scenes for audiences and services: rooms, a café, an office, a lobby, a server aisle, a courtyard, a cabling close-up. They show no brand logos, no MikroTik-like hardware, and no people. They are not presented as the company's office, team or customers.
3. There is no background video: real photography is stronger, and a video would cost visitors on slow connections several megabytes.

| File | Scene | Used on |
|---|---|---|
| solution-home | apartment living room, golden hour, white towers outside | Home, Solutions |
| solution-small-business | café/shop before opening | Home, Solutions |
| solution-office | open-plan office, late sun | Home, Solutions |
| solution-commercial | hotel lobby | Home, Solutions |
| solution-enterprise | unbranded server aisle, amber patch cords | Home, Solutions |
| solution-outdoor | courtyard and warehouse at dusk, foothills | Home, Solutions |
| services-cabling | patch panel macro on the amber/grey studio backdrop | Home services band, Services page |

## Accessibility checklist

- [x] Skip link, landmarks (`header`/`nav`/`main`/`footer`), one H1 per page, ordered headings
- [x] Visible focus ring (`signal-700`, 2 px, offset 3 px)
- [x] Touch targets ≥ 44 px; inputs are 16 px (no iOS zoom)
- [x] Form labels, hints and errors linked; errors near fields and summarised at the top
- [x] Alt text on every product photo (per view and language); decorative thumbnails use `alt=""`
- [x] Availability uses text plus colour; never colour alone
- [x] `prefers-reduced-motion` respected; no autoplay media
- [x] `lang` attribute per locale; language links carry `hreflang` and `lang`
