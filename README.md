# Darculight

A JetBrains IDE colour scheme plugin. Hand-crafted editor colour schemes that bring Darcula's familiar syntax colours to a light background — inverted contrast, adjusted tones, same feel.

The plugin bundles UI themes so the surrounding IDE interface (toolbars, menus, tabs, sidebars, etc.) complements the editor schemes. The UI themes are based on the standard **Light with Light Header** theme that ships with JetBrains IDEs.

## Colour Schemes

The `.icls` colour scheme files are the core of this project — hand-crafted, Darcula-parented schemes with light backgrounds.

| Scheme | Background | Notes |
|---|---|---|
| **Darculight** | White | Base variant |
| **Darculight contrast** | White | Higher contrast |
| **Darculight contrast sunshine** | Warm cream `#faf9f4` | Higher contrast, warm tint |

All three are available independently under *Settings > Editor > Colour Scheme*.

## UI Themes

Each colour scheme is paired with a UI theme that styles the surrounding IDE interface to match. The UI themes are intentionally close to JetBrains' built-in Light with Light Header — just tuned to pair with each scheme's accent colours.

## Structure

```
src/main/resources/
├── META-INF/plugin.xml          # Plugin descriptor
├── themes/*.theme.json          # UI themes (IDE interface styling)
└── colors/*.icls                # Editor colour schemes (hand-crafted, do not auto-generate)
```

## Building

Requires Java 21+. Install with `sudo apt install -y openjdk-21-jdk`. Then run:

```
./gradlew
```

Output: `build/distributions/darculight.zip`

Install via **Settings → Plugins → ⚙ → Install Plugin from Disk**.