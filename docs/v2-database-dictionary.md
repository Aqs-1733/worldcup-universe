# WorldCup Universe V2｜数据库字典

从本次迁移后的实际 MySQL schema 导出。空白来源不推断为已核验。

## `admin_posts`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`author_id` | `bigint(20) unsigned` | 是 | NULL |
`title` | `varchar(180)` | 否 | NULL |
`body` | `mediumtext` | 否 | NULL |
`post_type` | `enum('announcement','team','personal','match')` | 否 | announcement |
`status` | `enum('draft','published')` | 否 | published |
`published_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`author_id` → `users` (`id`)。

## `ai_generations`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 是 | NULL |
`generation_type` | `enum('chat','image','vision')` | 否 | chat |
`prompt` | `text` | 否 | NULL |
`tone` | `varchar(80)` | 是 | NULL |
`target_team` | `varchar(120)` | 是 | NULL |
`response_text` | `mediumtext` | 是 | NULL |
`image_url` | `varchar(900)` | 是 | NULL |
`model_name` | `varchar(160)` | 是 | NULL |
`status` | `enum('success','failed')` | 否 | success |
`error_message` | `text` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`user_id` → `users` (`id`)。

## `audit_logs`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 是 | NULL |
`action` | `varchar(120)` | 否 | NULL |
`entity_type` | `varchar(80)` | 是 | NULL |
`entity_id` | `bigint(20) unsigned` | 是 | NULL |
`ip_address` | `varchar(64)` | 是 | NULL |
`detail_json` | `json` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`user_id` → `users` (`id`)。

## `coaches`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 否 | NULL |
`name_cn` | `varchar(160)` | 是 | NULL |
`name_original` | `varchar(220)` | 否 | NULL |
`nationality` | `varchar(120)` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`team_id` → `teams` (`id`)。

## `comments`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`entity_type` | `enum('team','player','match','news','site')` | 否 | site |
`entity_id` | `bigint(20) unsigned` | 是 | NULL |
`body` | `text` | 否 | NULL |
`status` | `enum('visible','hidden','pending')` | 否 | visible |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`user_id` → `users` (`id`)。

## `country_profiles`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`country_code` | `varchar(8)` | 否 | NULL |
`country_name_cn` | `varchar(120)` | 是 | NULL |
`country_name_original` | `varchar(160)` | 否 | NULL |
`capital` | `varchar(180)` | 是 | NULL |
`region` | `varchar(120)` | 是 | NULL |
`subregion` | `varchar(120)` | 是 | NULL |
`languages` | `varchar(300)` | 是 | NULL |
`currencies` | `varchar(300)` | 是 | NULL |
`population` | `bigint(20) unsigned` | 是 | NULL |
`area_km2` | `decimal(12,2)` | 是 | NULL |
`latitude` | `decimal(9,6)` | 是 | NULL |
`longitude` | `decimal(9,6)` | 是 | NULL |
`map_url` | `varchar(900)` | 是 | NULL |
`fifa_team_count` | `int(11)` | 否 | 0 |
`travel_summary` | `text` | 是 | NULL |
`culture_summary` | `text` | 是 | NULL |
`source_url` | `varchar(900)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `coursework_files`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`scope` | `varchar(16)` | 否 | NULL |
`title` | `varchar(200)` | 否 | NULL |
`storage_path` | `varchar(500)` | 否 | NULL |
`is_visible` | `tinyint(1)` | 否 | 0 |

## `data_quality_checks`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`check_key` | `varchar(120)` | 否 | NULL |
`label` | `varchar(200)` | 否 | NULL |
`expected_value` | `varchar(120)` | 是 | NULL |
`actual_value` | `varchar(120)` | 是 | NULL |
`status` | `enum('pass','warn','fail')` | 否 | warn |
`detail` | `text` | 是 | NULL |
`source_url` | `varchar(900)` | 是 | NULL |
`checked_at` | `datetime` | 否 | CURRENT_TIMESTAMP |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `data_sources`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`source_key` | `varchar(80)` | 否 | NULL |
`name` | `varchar(120)` | 否 | NULL |
`url` | `varchar(800)` | 否 | NULL |
`source_type` | `enum('official','api','wiki','rss','manual')` | 否 | api |
`trust_level` | `tinyint(3) unsigned` | 否 | 80 |
`last_synced_at` | `datetime` | 是 | NULL |
`last_status` | `varchar(40)` | 是 | NULL |
`last_error` | `text` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `fan_preferences`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`push_match` | `tinyint(1)` | 否 | 1 |
`push_tactics` | `tinyint(1)` | 否 | 1 |
`push_team` | `tinyint(1)` | 否 | 1 |
`push_fanmade` | `tinyint(1)` | 否 | 1 |
`push_entertainment` | `tinyint(1)` | 否 | 1 |
`preferred_language` | `varchar(20)` | 否 | zh-CN |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`user_id` → `users` (`id`)。

## `fan_profiles`

主键：`user_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`main_team_id` | `bigint(20) unsigned` | 是 | NULL |
`bio` | `varchar(500)` | 是 | NULL |
`updated_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

外键：`main_team_id` → `teams` (`id`)。

外键：`user_id` → `users` (`id`)。

## `forum_categories`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`slug` | `varchar(60)` | 否 | NULL |
`name` | `varchar(80)` | 否 | NULL |
`description` | `varchar(300)` | 是 | NULL |
`sort_order` | `int(11)` | 否 | 0 |

## `forum_replies`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`thread_id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`parent_id` | `bigint(20) unsigned` | 是 | NULL |
`body` | `text` | 否 | NULL |
`status` | `varchar(16)` | 否 | visible |
`created_at` | `datetime` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

外键：`parent_id` → `forum_replies` (`id`)。

外键：`thread_id` → `forum_threads` (`id`)。

外键：`user_id` → `users` (`id`)。

## `forum_reports`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`reporter_id` | `bigint(20) unsigned` | 否 | NULL |
`thread_id` | `bigint(20) unsigned` | 否 | NULL |
`reply_id` | `bigint(20) unsigned` | 是 | NULL |
`reason` | `varchar(500)` | 否 | NULL |
`status` | `varchar(16)` | 否 | open |
`reviewed_by` | `bigint(20) unsigned` | 是 | NULL |
`created_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

外键：`reply_id` → `forum_replies` (`id`)。

外键：`reporter_id` → `users` (`id`)。

外键：`reviewed_by` → `users` (`id`)。

外键：`thread_id` → `forum_threads` (`id`)。

## `forum_threads`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`author_id` | `bigint(20) unsigned` | 否 | NULL |
`category_id` | `bigint(20) unsigned` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 是 | NULL |
`match_id` | `bigint(20) unsigned` | 是 | NULL |
`title` | `varchar(160)` | 否 | NULL |
`body` | `text` | 否 | NULL |
`status` | `varchar(16)` | 否 | published |
`is_locked` | `tinyint(1)` | 否 | 0 |
`is_pinned` | `tinyint(1)` | 否 | 0 |
`views` | `int(11) unsigned` | 否 | 0 |
`last_activity_at` | `datetime` | 否 | CURRENT_TIMESTAMP |
`created_at` | `datetime` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

外键：`author_id` → `users` (`id`)。

外键：`category_id` → `forum_categories` (`id`)。

外键：`match_id` → `matches` (`id`)。

外键：`team_id` → `teams` (`id`)。

## `match_events`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`external_event_id` | `varchar(100)` | 是 | NULL |
`match_id` | `bigint(20) unsigned` | 是 | NULL |
`minute` | `int(11)` | 是 | NULL |
`event_type` | `varchar(80)` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 是 | NULL |
`player_id` | `bigint(20) unsigned` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`raw_payload_json` | `json` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public

外键：`match_id` → `matches` (`id`)。

外键：`official_import_id` → `official_imports` (`id`)。

外键：`player_id` → `players` (`id`)。

外键：`team_id` → `teams` (`id`)。

## `match_lineups`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`external_lineup_id` | `varchar(100)` | 是 | NULL |
`match_id` | `bigint(20) unsigned` | 是 | NULL |
`player_id` | `bigint(20) unsigned` | 是 | NULL |
`team_id` | `bigint(20) unsigned` | 是 | NULL |
`is_starting_xi` | `tinyint(1)` | 否 | 0 |
`tactical_position` | `varchar(40)` | 是 | NULL |
`minutes_played` | `int(11)` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`raw_payload_json` | `json` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public

外键：`match_id` → `matches` (`id`)。

外键：`official_import_id` → `official_imports` (`id`)。

外键：`player_id` → `players` (`id`)。

外键：`team_id` → `teams` (`id`)。

## `match_prediction_features`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`external_match_id` | `varchar(100)` | 否 | NULL |
`match_id` | `bigint(20) unsigned` | 是 | NULL |
`home_team_id` | `bigint(20) unsigned` | 是 | NULL |
`away_team_id` | `bigint(20) unsigned` | 是 | NULL |
`feature_payload_json` | `json` | 否 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`away_team_id` → `teams` (`id`)。

外键：`home_team_id` → `teams` (`id`)。

外键：`match_id` → `matches` (`id`)。

## `match_progressions`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`from_match_id` | `bigint(20) unsigned` | 否 | NULL |
`to_match_id` | `bigint(20) unsigned` | 否 | NULL |
`outcome` | `varchar(16)` | 否 | winner |
`slot` | `varchar(8)` | 否 | NULL |
`source_url` | `varchar(800)` | 否 | NULL |
`is_verified` | `tinyint(1)` | 否 | 0 |

外键：`from_match_id` → `matches` (`id`)。

外键：`to_match_id` → `matches` (`id`)。

## `match_stats`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`match_id` | `bigint(20) unsigned` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 是 | NULL |
`possession` | `decimal(5,2)` | 是 | NULL |
`shots` | `int(11)` | 是 | NULL |
`shots_on_target` | `int(11)` | 是 | NULL |
`corners` | `int(11)` | 是 | NULL |
`fouls` | `int(11)` | 是 | NULL |
`yellow_cards` | `int(11)` | 是 | NULL |
`red_cards` | `int(11)` | 是 | NULL |
`expected_goals` | `decimal(5,2)` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public

外键：`match_id` → `matches` (`id`)。

外键：`official_import_id` → `official_imports` (`id`)。

外键：`team_id` → `teams` (`id`)。

## `match_videos`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`slug` | `varchar(140)` | 否 | NULL |
`title` | `varchar(220)` | 否 | NULL |
`tournament_year` | `int(11)` | 否 | NULL |
`stage` | `varchar(60)` | 否 | NULL |
`home_name` | `varchar(80)` | 否 | NULL |
`away_name` | `varchar(80)` | 否 | NULL |
`home_code` | `char(3)` | 否 | NULL |
`away_code` | `char(3)` | 否 | NULL |
`provider` | `varchar(20)` | 否 | youtube |
`provider_id` | `varchar(80)` | 否 | NULL |
`source_url` | `varchar(800)` | 否 | NULL |
`source_author` | `varchar(80)` | 否 | NULL |
`source_title` | `varchar(280)` | 否 | NULL |
`thumbnail_url` | `varchar(800)` | 是 | NULL |
`video_type` | `varchar(20)` | 否 | full_match |
`status` | `varchar(20)` | 否 | draft |
`verified_at` | `datetime` | 是 | NULL |
`created_at` | `datetime` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

## `matches`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`external_id` | `varchar(100)` | 是 | NULL |
`stage` | `varchar(80)` | 否 | NULL |
`group_name` | `varchar(20)` | 是 | NULL |
`home_team_id` | `bigint(20) unsigned` | 是 | NULL |
`away_team_id` | `bigint(20) unsigned` | 是 | NULL |
`home_team_name` | `varchar(160)` | 是 | NULL |
`away_team_name` | `varchar(160)` | 是 | NULL |
`home_score` | `int(11)` | 是 | NULL |
`away_score` | `int(11)` | 是 | NULL |
`home_penalty_score` | `int(11)` | 是 | NULL |
`away_penalty_score` | `int(11)` | 是 | NULL |
`status` | `enum('scheduled','live','finished','postponed','cancelled','unknown')` | 否 | unknown |
`starts_at` | `datetime` | 是 | NULL |
`venue_id` | `bigint(20) unsigned` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public
`match_number` | `int(11)` | 是 | NULL |
`winner_fifa_id` | `varchar(80)` | 是 | NULL |
`placeholder_home` | `varchar(80)` | 是 | NULL |
`placeholder_away` | `varchar(80)` | 是 | NULL |

外键：`away_team_id` → `teams` (`id`)。

外键：`home_team_id` → `teams` (`id`)。

外键：`official_import_id` → `official_imports` (`id`)。

外键：`venue_id` → `venues` (`id`)。

## `media_assets`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`asset_key` | `varchar(80)` | 否 | NULL |
`path` | `varchar(800)` | 否 | NULL |
`alt_text` | `varchar(300)` | 否 | NULL |
`source_url` | `varchar(800)` | 否 | NULL |
`license_name` | `varchar(120)` | 否 | NULL |
`credit` | `varchar(300)` | 是 | NULL |
`status` | `varchar(16)` | 否 | pending |

## `migration`

主键：`version`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`version` | `varchar(180)` | 否 | NULL |
`apply_time` | `int(11)` | 是 | NULL |

## `news_article_tags`

主键：`article_id`,`tag_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`article_id` | `bigint(20) unsigned` | 否 | NULL |
`tag_id` | `bigint(20) unsigned` | 否 | NULL |

外键：`article_id` → `news_articles` (`id`)。

外键：`tag_id` → `tags` (`id`)。

## `news_articles`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`source_id` | `bigint(20) unsigned` | 是 | NULL |
`source_name` | `varchar(120)` | 否 | NULL |
`source_url` | `varchar(900)` | 否 | NULL |
`title_cn` | `varchar(280)` | 是 | NULL |
`title_original` | `varchar(280)` | 否 | NULL |
`summary_cn` | `text` | 是 | NULL |
`summary_original` | `text` | 是 | NULL |
`content_cn` | `mediumtext` | 是 | NULL |
`content_original` | `mediumtext` | 是 | NULL |
`language_code` | `varchar(20)` | 否 | en |
`published_at` | `datetime` | 是 | NULL |
`fetched_at` | `datetime` | 否 | CURRENT_TIMESTAMP |
`credibility_score` | `tinyint(3) unsigned` | 否 | 80 |
`translation_status` | `enum('none','pending','translated','failed')` | 否 | none |
`raw_payload_json` | `json` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`status` | `varchar(16)` | 否 | published | draft/published/hidden

外键：`source_id` → `news_sources` (`id`)。

## `news_sources`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`name` | `varchar(120)` | 否 | NULL |
`url` | `varchar(800)` | 否 | NULL |
`rss_url` | `varchar(800)` | 是 | NULL |
`language_code` | `varchar(20)` | 否 | en |
`trust_level` | `tinyint(3) unsigned` | 否 | 80 |
`is_enabled` | `tinyint(1)` | 否 | 1 |
`last_synced_at` | `datetime` | 是 | NULL |
`last_error` | `text` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `notifications`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`actor_id` | `bigint(20) unsigned` | 是 | NULL |
`thread_id` | `bigint(20) unsigned` | 否 | NULL |
`type` | `varchar(32)` | 否 | NULL |
`read_at` | `datetime` | 是 | NULL |
`created_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

外键：`actor_id` → `users` (`id`)。

外键：`thread_id` → `forum_threads` (`id`)。

外键：`user_id` → `users` (`id`)。

## `official_imports`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`provider` | `varchar(40)` | 否 | NULL |
`manifest_sha256` | `char(64)` | 否 | NULL |
`snapshot_path` | `varchar(500)` | 否 | NULL |
`manifest_json` | `json` | 否 | NULL |
`report_json` | `json` | 是 | NULL |
`imported_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

## `player_statistics`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`player_id` | `bigint(20) unsigned` | 是 | NULL |
`external_player_id` | `varchar(80)` | 是 | NULL |
`matches_played` | `int(11)` | 是 | NULL |
`matches_started` | `int(11)` | 是 | NULL |
`minutes_played` | `int(11)` | 是 | NULL |
`goals` | `int(11)` | 是 | NULL |
`assists` | `int(11)` | 是 | NULL |
`shots` | `int(11)` | 是 | NULL |
`shots_on_target` | `int(11)` | 是 | NULL |
`yellow_cards` | `int(11)` | 是 | NULL |
`red_cards` | `int(11)` | 是 | NULL |
`penalty_goals` | `int(11)` | 是 | NULL |
`own_goals` | `int(11)` | 是 | NULL |
`clean_sheets` | `int(11)` | 是 | NULL |
`saves` | `int(11)` | 是 | NULL |
`goals_conceded` | `int(11)` | 是 | NULL |
`average_rating` | `decimal(5,2)` | 是 | NULL |
`data_source` | `varchar(160)` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`raw_payload_json` | `json` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public

外键：`official_import_id` → `official_imports` (`id`)。

外键：`player_id` → `players` (`id`)。

## `players`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 是 | NULL |
`fifa_id` | `varchar(80)` | 是 | NULL |
`slug` | `varchar(160)` | 否 | NULL |
`name_cn` | `varchar(160)` | 是 | NULL |
`name_original` | `varchar(220)` | 否 | NULL |
`position` | `varchar(40)` | 是 | NULL |
`shirt_number` | `int(11)` | 是 | NULL |
`birth_date` | `date` | 是 | NULL |
`age` | `int(11)` | 是 | NULL |
`club` | `varchar(180)` | 是 | NULL |
`caps` | `int(11)` | 是 | NULL |
`goals` | `int(11)` | 是 | NULL |
`height_cm` | `int(11)` | 是 | NULL |
`photo_url` | `varchar(800)` | 是 | NULL |
`popularity_score` | `decimal(8,2)` | 否 | 0.00 |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public

外键：`official_import_id` → `official_imports` (`id`)。

外键：`team_id` → `teams` (`id`)。

## `referees`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`external_id` | `varchar(80)` | 是 | NULL |
`name_cn` | `varchar(160)` | 是 | NULL |
`name_original` | `varchar(220)` | 否 | NULL |
`country_code` | `char(3)` | 是 | NULL |
`avg_cards_per_game` | `decimal(5,2)` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `roles`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`name` | `varchar(50)` | 否 | NULL |
`label` | `varchar(100)` | 否 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `source_files`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`dataset_key` | `varchar(120)` | 否 | NULL |
`source_name` | `varchar(160)` | 否 | NULL |
`source_path` | `varchar(900)` | 否 | NULL |
`source_url` | `varchar(900)` | 是 | NULL |
`file_type` | `varchar(20)` | 否 | NULL |
`row_count` | `int(11)` | 否 | 0 |
`sha256` | `char(64)` | 是 | NULL |
`imported_at` | `datetime` | 否 | CURRENT_TIMESTAMP |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `source_records`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`source_file_id` | `bigint(20) unsigned` | 否 | NULL |
`row_number` | `int(11)` | 否 | NULL |
`entity_key` | `varchar(160)` | 是 | NULL |
`record_json` | `json` | 否 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`source_file_id` → `source_files` (`id`)。

## `standings`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 否 | NULL |
`group_name` | `varchar(20)` | 否 | NULL |
`played` | `int(11)` | 否 | 0 |
`won` | `int(11)` | 否 | 0 |
`drawn` | `int(11)` | 否 | 0 |
`lost` | `int(11)` | 否 | 0 |
`goals_for` | `int(11)` | 否 | 0 |
`goals_against` | `int(11)` | 否 | 0 |
`goal_difference` | `int(11)` | 否 | 0 |
`points` | `int(11)` | 否 | 0 |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public

外键：`official_import_id` → `official_imports` (`id`)。

外键：`team_id` → `teams` (`id`)。

## `tags`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`slug` | `varchar(80)` | 否 | NULL |
`name_cn` | `varchar(80)` | 否 | NULL |
`color` | `varchar(20)` | 否 | #16a34a |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `team_members`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`name` | `varchar(80)` | 否 | NULL |
`student_no` | `varchar(50)` | 是 | NULL |
`role_name` | `varchar(80)` | 否 | NULL |
`bio` | `text` | 是 | NULL |
`photo_url` | `varchar(500)` | 是 | NULL |
`sort_order` | `int(11)` | 否 | 0 |
`is_visible` | `tinyint(1)` | 否 | 1 |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `teams`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`fifa_id` | `varchar(80)` | 是 | NULL |
`slug` | `varchar(120)` | 否 | NULL |
`code` | `varchar(8)` | 是 | NULL |
`name_cn` | `varchar(120)` | 是 | NULL |
`name_original` | `varchar(160)` | 否 | NULL |
`country_code` | `varchar(8)` | 是 | NULL |
`flag_emoji` | `varchar(16)` | 是 | NULL |
`flag_url` | `varchar(800)` | 是 | NULL |
`confederation` | `varchar(40)` | 是 | NULL |
`group_name` | `varchar(20)` | 是 | NULL |
`coach_name` | `varchar(160)` | 是 | NULL |
`world_ranking` | `int(11)` | 是 | NULL |
`profile` | `text` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`official_import_id` | `bigint(20) unsigned` | 是 | NULL | Null = unverified legacy data, never public

外键：`official_import_id` → `official_imports` (`id`)。

## `thread_bookmarks`

主键：`thread_id`,`user_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`thread_id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`created_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

外键：`thread_id` → `forum_threads` (`id`)。

外键：`user_id` → `users` (`id`)。

## `thread_likes`

主键：`thread_id`,`user_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`thread_id` | `bigint(20) unsigned` | 否 | NULL |
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`created_at` | `datetime` | 否 | CURRENT_TIMESTAMP |

外键：`thread_id` → `forum_threads` (`id`)。

外键：`user_id` → `users` (`id`)。

## `tournament_stages`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`external_id` | `varchar(80)` | 是 | NULL |
`name` | `varchar(120)` | 否 | NULL |
`name_cn` | `varchar(120)` | 是 | NULL |
`stage_order` | `int(11)` | 是 | NULL |
`is_knockout` | `tinyint(1)` | 否 | 0 |
`source_url` | `varchar(800)` | 是 | NULL |
`source_synced_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

## `user_blocked_teams`

主键：`user_id`,`team_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 否 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`team_id` → `teams` (`id`)。

外键：`user_id` → `users` (`id`)。

## `user_favorite_players`

主键：`user_id`,`player_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`player_id` | `bigint(20) unsigned` | 否 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`player_id` → `players` (`id`)。

外键：`user_id` → `users` (`id`)。

## `user_favorite_teams`

主键：`user_id`,`team_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`team_id` | `bigint(20) unsigned` | 否 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`team_id` → `teams` (`id`)。

外键：`user_id` → `users` (`id`)。

## `user_roles`

主键：`user_id`,`role_id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`user_id` | `bigint(20) unsigned` | 否 | NULL |
`role_id` | `bigint(20) unsigned` | 否 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |

外键：`role_id` → `roles` (`id`)。

外键：`user_id` → `users` (`id`)。

## `users`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`username` | `varchar(40)` | 否 | NULL |
`email` | `varchar(120)` | 否 | NULL |
`password_hash` | `varchar(255)` | 否 | NULL |
`display_name` | `varchar(80)` | 是 | NULL |
`avatar_url` | `varchar(500)` | 是 | NULL |
`survey_completed_at` | `datetime` | 是 | NULL |
`survey_skipped_at` | `datetime` | 是 | NULL |
`last_login_at` | `datetime` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`auth_key` | `varchar(64)` | 否 |  | Yii2 identity key
`status` | `varchar(16)` | 否 | active | active/disabled

## `venues`

主键：`id`。

字段 | 类型 | 可空 | 默认值 | 注释
---|---|---|---|---
`id` | `bigint(20) unsigned` | 否 | NULL |
`fifa_id` | `varchar(80)` | 是 | NULL |
`name_cn` | `varchar(160)` | 是 | NULL |
`name_original` | `varchar(220)` | 否 | NULL |
`city_cn` | `varchar(120)` | 是 | NULL |
`city_original` | `varchar(160)` | 是 | NULL |
`country_code` | `char(2)` | 是 | NULL |
`capacity` | `int(11)` | 是 | NULL |
`source_url` | `varchar(800)` | 是 | NULL |
`created_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
`updated_at` | `timestamp` | 否 | CURRENT_TIMESTAMP |
