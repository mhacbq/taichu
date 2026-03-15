# 八字合婚 API 文档

## 接口概览

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/hehun/pricing | GET | 获取定价配置 |
| /api/hehun/calculate | POST | 八字合婚计算 |
| /api/hehun/history | GET | 合婚历史记录 |
| /api/hehun/export | POST | 导出报告 |

---

## 1. 获取定价配置

### 请求
```
GET /api/hehun/pricing
```

### 响应
```json
{
  "code": 0,
  "data": {
    "tier": {
      "free": {
        "name": "免费预览",
        "price": 0,
        "features": ["基础合婚分数", "简单等级评定", "1条基础建议", "双方八字展示"],
        "is_available": true
      },
      "premium": {
        "name": "详细报告",
        "price": 80,
        "original_price": 80,
        "discount": {
          "percent": 50,
          "reason": "新用户专享",
          "saved": 40
        },
        "features": ["五维度详细评分", "AI深度分析", "专业化解方案", "流年合婚分析"],
        "is_available": true
      },
      "vip": {
        "name": "VIP特权",
        "price": 0,
        "features": ["所有详细报告功能", "无限次合婚分析", "导出报告免费"],
        "is_vip": false,
        "is_available": false
      }
    },
    "export": {
      "pdf_points": 30,
      "image_points": 30
    },
    "user_status": {
      "is_vip": false,
      "points": 100,
      "is_new_user": true
    }
  }
}
```

---

## 2. 八字合婚计算

### 免费预览层
```
POST /api/hehun/calculate
Content-Type: application/json

{
  "maleName": "张三",
  "maleBirthDate": "1990-05-15 12:00:00",
  "femaleName": "李四",
  "femaleBirthDate": "1992-08-20 14:00:00",
  "tier": "free"
}
```

### 付费详细层
```
POST /api/hehun/calculate
Content-Type: application/json

{
  "maleName": "张三",
  "maleBirthDate": "1990-05-15 12:00:00",
  "femaleName": "李四",
  "femaleBirthDate": "1992-08-20 14:00:00",
  "tier": "premium",
  "useAi": true
}
```

### 响应示例（付费层）
```json
{
  "code": 0,
  "data": {
    "id": 123,
    "tier": "premium",
    "male_bazi": {
      "year": { "gan": "庚", "zhi": "午", ... },
      "month": { "gan": "辛", "zhi": "巳", ... },
      "day": { "gan": "己", "zhi": "卯", ... },
      "hour": { "gan": "庚", "zhi": "午", ... }
    },
    "female_bazi": { ... },
    "hehun": {
      "score": 78,
      "max_score": 100,
      "level": "good",
      "level_text": "佳偶天成",
      "scores": {
        "year": 12,
        "day": 25,
        "wuxing": 15,
        "hechong": 15,
        "nayin": 11
      },
      "details": {
        "year": "生肖相合，属相婚配大吉...",
        "day": "日主相合，夫妻情深...",
        "wuxing": "五行互补良好...",
        "hechong": "干支多合少冲...",
        "nayin": "纳音相生..."
      },
      "suggestions": [
        "你们的命理配合良好，珍惜缘分...",
        "建议互相尊重、包容理解..."
      ],
      "comment": "张三与李四的八字配合良好...",
      "highlights": [
        { "type": "good", "text": "生肖相合" },
        { "type": "good", "text": "日主相生" }
      ]
    },
    "ai_analysis": {
      "summary": "根据八字合婚分析，张三与李四的命理契合度极高...",
      "personality_match": {
        "male_personality": "己土日主，性格稳重踏实...",
        "female_personality": "戊土日主，性格坚强果断...",
        "match_analysis": "双方性格互补性强..."
      },
      "marriage_prospect": "婚姻前景乐观...",
      "career_wealth": "事业财运配合良好...",
      "children_fate": "子女缘分较好...",
      "suggestions": ["建议1", "建议2", "建议3"],
      "auspicious_info": {
        "best_years": ["2025年", "2026年"],
        "auspicious_months": ["农历二月", "农历八月"],
        "notes": "避开双方生肖相冲的月份"
      },
      "is_ai_generated": true,
      "provider": "deepseek"
    },
    "pricing": {
      "points_cost": 40,
      "original_points": 80,
      "discount": {
        "percent": 50,
        "reason": "新用户专享",
        "saved": 40
      }
    },
    "user_status": {
      "remaining_points": 60,
      "is_vip": false,
      "is_vip_free": false
    }
  }
}
```

---

## 3. 导出报告

### 请求
```
POST /api/hehun/export
Content-Type: application/json

{
  "record_id": 123,
  "format": "pdf",
  "template": "default"
}
```

### 响应
```json
{
  "code": 0,
  "data": {
    "record_id": 123,
    "format": "pdf",
    "template": "default",
    "download_url": "https://example.com/storage/hehun/hehun_xxx.pdf",
    "expires_at": "2025-04-15 10:30:00",
    "points_cost": 30,
    "remaining_points": 30
  }
}
```

---

## 4. 获取历史记录

### 请求
```
GET /api/hehun/history?limit=20
```

### 响应
```json
{
  "code": 0,
  "data": [
    {
      "id": 123,
      "male_name": "张三",
      "female_name": "李四",
      "score": 78,
      "level": "good",
      "create_time": "2025-03-15 10:30:00"
    }
  ]
}
```

---

## 错误码

| 错误码 | 说明 |
|--------|------|
| 0 | 成功 |
| 400 | 参数错误 |
| 403 | 积分不足 |
| 404 | 记录不存在 |
| 500 | 服务器错误 |

---

## 合婚等级说明

| 分数 | 等级 | 说明 |
|------|------|------|
| 85-100 | excellent | 天作之合 |
| 70-84 | good | 佳偶天成 |
| 55-69 | medium | 中等婚配 |
| 40-54 | fair | 需加经营 |
| 0-39 | poor | 谨慎考虑 |
