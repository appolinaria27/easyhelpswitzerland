#!/usr/bin/env python3
"""Generate og-image.jpg for Easy Help Switzerland (1200×630)."""

import urllib.request
import ssl
import io
from PIL import Image, ImageDraw, ImageFont

_ssl_ctx = ssl._create_unverified_context()

W, H = 1200, 630
NAVY = (20, 27, 55)

# ── 1. Base: dark navy canvas ────────────────────────────────────────────────
img = Image.new("RGB", (W, H), NAVY)
draw = ImageDraw.Draw(img)

# ── 2. Background photo from Unsplash ───────────────────────────────────────
try:
    url = "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200&q=80"
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, timeout=15, context=_ssl_ctx) as r:
        bg = Image.open(io.BytesIO(r.read())).convert("RGB")
    # Crop to 1200×630 centered
    bw, bh = bg.size
    ratio = max(W / bw, H / bh)
    nw, nh = int(bw * ratio), int(bh * ratio)
    bg = bg.resize((nw, nh), Image.LANCZOS)
    ox = (nw - W) // 2
    oy = int((nh - H) * 0.4)
    bg = bg.crop((ox, oy, ox + W, oy + H))
    img.paste(bg, (0, 0))
    print("Background photo loaded.")
except Exception as e:
    print(f"Could not load background photo: {e} — using gradient.")
    # Gradient fallback
    for y in range(H):
        t = y / H
        r = int(20 + t * 10)
        g = int(27 + t * 8)
        b = int(55 + t * 20)
        draw.line([(0, y), (W, y)], fill=(r, g, b))

# ── 3. Dark overlay (diagonal gradient simulation via alpha) ─────────────────
overlay = Image.new("RGBA", (W, H), (0, 0, 0, 0))
ov_draw = ImageDraw.Draw(overlay)
for x in range(W):
    t = x / W
    alpha = int(235 - t * 100)  # 235→135 left to right
    ov_draw.line([(x, 0), (x, H)], fill=(20, 27, 55, alpha))
img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
draw = ImageDraw.Draw(img)

# ── 4. Fonts ─────────────────────────────────────────────────────────────────
SUPP = "/System/Library/Fonts/Supplemental"
SYS  = "/System/Library/Fonts"

def font(name, size):
    try:
        return ImageFont.truetype(f"{SUPP}/{name}", size)
    except:
        try:
            return ImageFont.truetype(f"{SYS}/{name}", size)
        except:
            return ImageFont.load_default()

f_heading   = font("Georgia Bold.ttf",        80)
f_heading_r = font("Georgia.ttf",             80)
f_sub       = font("Arial.ttf",               20)
f_micro     = font("Arial.ttf",               12)
f_brand_main= font("Georgia.ttf",             26)
f_brand_sub = font("Arial.ttf",               11)
f_feature   = font("Arial.ttf",               13)
f_url       = font("Arial.ttf",               13)

# ── 5. Colors ────────────────────────────────────────────────────────────────
WHITE      = (255, 255, 255)
WHITE_68   = (180, 184, 205)
WHITE_45   = (140, 145, 168)
WHITE_35   = (110, 115, 138)
WHITE_60   = (160, 165, 190)

PAD_X = 72
PAD_Y = 64

# ── 6. Logo row ──────────────────────────────────────────────────────────────
# Simple house icon approximation using lines
lx, ly = PAD_X, PAD_Y
# Brand text
brand_x = lx + 58
draw.text((brand_x, ly + 2), "Easy Help", font=f_brand_main, fill=WHITE)
draw.text((brand_x, ly + 34), "S W I T Z E R L A N D", font=f_brand_sub, fill=WHITE_60)

# Simple geometric icon (two overlapping roof shapes)
ix, iy = lx, ly
pts = [(ix+4, iy+44), (ix+4, iy+10), (ix+14, iy+2), (ix+24, iy+10),
       (ix+24, iy+44)]
draw.line(pts, fill=WHITE, width=2)
pts2 = [(ix+14, iy+44), (ix+14, iy+22), (ix+24, iy+14),
        (ix+24, iy+44)]
draw.line(pts2, fill=WHITE, width=2)

# ── 7. Middle content ────────────────────────────────────────────────────────
mid_y = PAD_Y + 120

# Micro label
draw.text((PAD_X, mid_y), "Relocation consulting  ·  Zürich",
          font=f_micro, fill=WHITE_45)

# H1
h1_y = mid_y + 36
draw.text((PAD_X, h1_y), "Move to Switzerland", font=f_heading, fill=WHITE)
draw.text((PAD_X, h1_y + 88), "with confidence.", font=f_heading, fill=WHITE)

# Subline
sub_y = h1_y + 196
sub_text = "Permits, registration, documents and practical guidance —"
sub_text2 = "structured support for every step of your relocation."
draw.text((PAD_X, sub_y),      sub_text,  font=f_sub, fill=WHITE_68)
draw.text((PAD_X, sub_y + 30), sub_text2, font=f_sub, fill=WHITE_68)

# ── 8. Bottom row ────────────────────────────────────────────────────────────
bot_y = H - PAD_Y - 18

features = ["✓  Free first consultation", "✓  4 languages", "✓  Based in Zürich"]
fx = PAD_X
for feat in features:
    draw.text((fx, bot_y), feat, font=f_feature, fill=WHITE_68)
    bb = draw.textbbox((0, 0), feat, font=f_feature)
    fx += (bb[2] - bb[0]) + 32

# URL right-aligned
url_text = "easyhelpswitzerland.ch"
bb = draw.textbbox((0, 0), url_text, font=f_url)
url_w = bb[2] - bb[0]
draw.text((W - PAD_X - url_w, bot_y), url_text, font=f_url, fill=WHITE_35)

# ── 9. Thin accent line ──────────────────────────────────────────────────────
line_y = bot_y - 18
draw.line([(PAD_X, line_y), (W - PAD_X, line_y)],
          fill=(255, 255, 255, 40), width=1)

# ── 10. Save ─────────────────────────────────────────────────────────────────
out = "/Users/polinakravtsova/Desktop/New project/og-image.jpg"
img.save(out, "JPEG", quality=92, optimize=True)
print(f"Saved: {out}")
size = __import__('os').path.getsize(out)
print(f"Size: {size:,} bytes ({size//1024} KB)")
