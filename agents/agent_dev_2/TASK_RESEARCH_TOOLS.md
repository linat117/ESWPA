# Task: Research Tools Integration

**Agent**: agent_dev_2  
**Priority**: Medium-High  
**Status**: 📋 Ready for Development  
**Estimated Time**: 12-16 hours

---

## Objective
Integrate powerful research tools to help researchers work more efficiently and make the research panel addictive and engaging.

---

## Requirements

### Tool 1: PDF Viewer & Annotator

#### Features:
- Embedded PDF viewer using PDF.js
- Highlight text with colors
- Add notes/comments on pages
- Bookmark pages
- Search within PDF
- Zoom controls (fit width, fit page, custom zoom)
- Print functionality
- Download annotations
- Share annotations with collaborators

#### Database Table:
```sql
CREATE TABLE `pdf_annotations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `resource_id` INT(11) NULL,
  `research_file_id` INT(11) NULL,
  `page_number` INT(11) NOT NULL,
  `annotation_type` ENUM('highlight', 'note', 'bookmark', 'drawing') NOT NULL,
  `content` TEXT NULL,
  `coordinates` TEXT NULL,
  `color` VARCHAR(20) NULL,
  `rect` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`),
  INDEX `idx_resource_id` (`resource_id`),
  INDEX `idx_research_file_id` (`research_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Files to Create:
- `include/pdf_viewer.php` - PDF viewer component
- `include/pdf_annotations_handler.php` - Annotation CRUD
- `assets/js/pdf-annotator.js` - Annotation JavaScript
- `assets/css/pdf-viewer.css` - Viewer styles

---

### Tool 2: Citation Generator

#### Features:
- Generate citations in multiple formats:
  - APA (American Psychological Association)
  - MLA (Modern Language Association)
  - Chicago
  - Harvard
  - IEEE
- Auto-detect citation type from resource
- Copy to clipboard
- Export bibliography
- Save citation library
- Import from DOI/ISBN

#### Citation Formats:

**APA Format:**
```
Author, A. A. (Year). Title of work. Publisher. DOI/URL
```

**MLA Format:**
```
Author, First Name. Title of Work. Publisher, Year. DOI/URL
```

#### Database Table:
```sql
CREATE TABLE `member_citations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `resource_id` INT(11) NULL,
  `research_id` INT(11) NULL,
  `citation_format` VARCHAR(50) NOT NULL,
  `citation_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Files to Create:
- `include/citation_generator.php` - Citation generation logic
- `member-citations.php` - Citation library page
- `assets/js/citation-generator.js` - Client-side citation tool

---

### Tool 3: Bibliography Manager

#### Features:
- Create bibliography collections
- Add resources to bibliography
- Organize by category/topic
- Export bibliography (PDF, Word, BibTeX)
- Share bibliography with others
- Import from BibTeX
- Auto-generate bibliography from favorites

#### Database Table:
```sql
CREATE TABLE `bibliography_collections` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `is_public` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bibliography_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `collection_id` INT(11) NOT NULL,
  `resource_id` INT(11) NULL,
  `research_id` INT(11) NULL,
  `citation_text` TEXT NOT NULL,
  `notes` TEXT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_collection_id` (`collection_id`),
  FOREIGN KEY (`collection_id`) REFERENCES `bibliography_collections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Files to Create:
- `member-bibliography.php` - Bibliography management page
- `include/bibliography_handler.php` - Bibliography CRUD

---

### Tool 4: Note-Taking Tool

#### Features:
- Rich text editor (TinyMCE or similar)
- Organize notes by research/project
- Tag notes for easy search
- Link notes to specific resources/research
- Full-text search
- Export notes (PDF, Word, Markdown)
- Share notes with collaborators
- Version history

#### Database Table:
```sql
CREATE TABLE `research_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `research_id` INT(11) NULL,
  `resource_id` INT(11) NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `tags` TEXT NULL,
  `is_shared` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`),
  INDEX `idx_research_id` (`research_id`),
  FULLTEXT KEY `idx_content` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Files to Create:
- `member-notes.php` - Notes management page
- `include/notes_handler.php` - Notes CRUD
- `assets/js/notes-editor.js` - Rich text editor integration

---

### Tool 5: Reading Progress Tracker

#### Features:
- Track reading progress for resources/research
- Last read position (page number)
- Time spent reading
- Reading goals (daily/weekly/monthly)
- Progress visualization (charts)
- Reading streaks
- Reading statistics

#### Database Table:
```sql
CREATE TABLE `reading_progress` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `resource_id` INT(11) NULL,
  `research_id` INT(11) NULL,
  `page_number` INT(11) DEFAULT 1,
  `total_pages` INT(11) NULL,
  `time_spent_minutes` INT(11) DEFAULT 0,
  `last_read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed` TINYINT(1) DEFAULT 0,
  `completed_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_progress` (`member_id`, `resource_id`, `research_id`),
  INDEX `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reading_goals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `goal_type` ENUM('daily', 'weekly', 'monthly') NOT NULL,
  `target_minutes` INT(11) NOT NULL,
  `current_minutes` INT(11) DEFAULT 0,
  `goal_period_start` DATE NOT NULL,
  `goal_period_end` DATE NOT NULL,
  `completed` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Files to Create:
- `member-reading-progress.php` - Progress dashboard
- `include/reading_tracker.php` - Progress tracking functions
- `assets/js/reading-tracker.js` - Client-side tracking

---

### Tool 6: Quick Actions Toolbar

#### Features:
- Floating toolbar for quick actions
- Quick note creation
- Quick citation copy
- Quick bookmark
- Quick share
- Keyboard shortcuts
- Context-aware actions

#### Files to Create:
- `include/quick_actions_toolbar.php` - Toolbar component
- `assets/js/quick-actions.js` - Toolbar JavaScript

---

### Tool 7: Research Statistics Dashboard

#### Features:
- Personal research statistics
- Resources read/downloaded
- Research projects created/collaborated
- Reading time tracking
- Achievement badges (for using tools)
- Progress charts
- Comparison with other members (optional, anonymized)

#### Files to Create:
- `member-research-stats.php` - Statistics dashboard
- `include/research_statistics.php` - Statistics calculation

---

## Making It Addictive

### Gamification Elements:
1. **Achievement Badges** for tool usage
   - "Annotation Master" - Created 100 annotations
   - "Citation Expert" - Generated 50 citations
   - "Note Taker" - Created 100 notes
   - "Reading Champion" - Read 50 resources

2. **Streaks**
   - Daily login streak
   - Daily reading streak
   - Research activity streak

3. **Progress Visualization**
   - Visual progress bars
   - Charts and graphs
   - Milestone celebrations

4. **Notifications**
   - "You're on a 5-day reading streak!"
   - "New resource matching your interests"
   - "Someone commented on your research"

5. **Social Elements**
   - Share achievements
   - Compare progress (anonymized)
   - Leaderboards (optional)

---

## Implementation Steps

1. Create database tables for all tools
2. Implement PDF viewer and annotator
3. Build citation generator
4. Create bibliography manager
5. Integrate note-taking tool
6. Add reading progress tracker
7. Create quick actions toolbar
8. Build statistics dashboard
9. Add gamification elements
10. Test all tools thoroughly

---

## Files to Create

### Backend:
1. `include/pdf_viewer.php`
2. `include/pdf_annotations_handler.php`
3. `include/citation_generator.php`
4. `include/bibliography_handler.php`
5. `include/notes_handler.php`
6. `include/reading_tracker.php`
7. `include/research_statistics.php`
8. `include/quick_actions_toolbar.php`

### Frontend:
1. `member-annotations.php`
2. `member-citations.php`
3. `member-bibliography.php`
4. `member-notes.php`
5. `member-reading-progress.php`
6. `member-research-stats.php`

### JavaScript:
1. `assets/js/pdf-annotator.js`
2. `assets/js/citation-generator.js`
3. `assets/js/notes-editor.js`
4. `assets/js/reading-tracker.js`
5. `assets/js/quick-actions.js`

### CSS:
1. `assets/css/pdf-viewer.css`
2. `assets/css/research-tools.css`

### SQL:
1. `Sql/migration_research_tools.sql`

---

## Testing Checklist

- [ ] PDF viewer loads correctly
- [ ] Annotations save and load
- [ ] Citation generator works for all formats
- [ ] Bibliography manager functions correctly
- [ ] Notes editor works
- [ ] Reading progress tracks accurately
- [ ] Statistics calculate correctly
- [ ] Quick actions toolbar works
- [ ] Gamification elements work
- [ ] Mobile responsive
- [ ] No JavaScript errors
- [ ] Performance optimized

---

**Last Updated**: December 16, 2025

