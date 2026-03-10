#!/usr/bin/env python3
"""
Chinese word segmentation using jieba.

Usage:
    python3 scripts/segment.py "你好世界"
    echo "你好世界" | python3 scripts/segment.py

Output:
    JSON array of segmented words: ["你好", "世界"]
"""
import json
import sys

import jieba


def segment(text: str) -> list[str]:
    words = jieba.lcut(text, HMM=False)
    return [w.strip() for w in words if w.strip()]


if __name__ == "__main__":
    if len(sys.argv) > 1:
        text = sys.argv[1]
    else:
        text = sys.stdin.read().strip()

    result = segment(text)
    print(json.dumps(result, ensure_ascii=False))
