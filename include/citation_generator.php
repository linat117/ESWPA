<?php
/**
 * Citation Generator
 * Generates citations in various formats (APA, MLA, Chicago, Harvard, IEEE)
 */

/**
 * Generate citation for a resource
 * 
 * @param array $resource Resource data array
 * @param string $format Citation format (apa, mla, chicago, harvard, ieee)
 * @return string Formatted citation
 */
function generateResourceCitation($resource, $format = 'apa') {
    $format = strtolower($format);
    
    $author = $resource['author'] ?? 'Unknown Author';
    $title = $resource['title'] ?? 'Untitled';
    $section = $resource['section'] ?? '';
    $publication_date = $resource['publication_date'] ?? date('Y');
    $doi = $resource['doi'] ?? '';
    $url = $resource['pdf_file'] ?? '';
    
    // Parse author name
    $authorParts = explode(' ', trim($author));
    $lastName = end($authorParts);
    $firstName = !empty($authorParts[0]) ? $authorParts[0] : '';
    $initials = !empty($firstName) ? substr($firstName, 0, 1) . '.' : '';
    
    switch ($format) {
        case 'apa':
            return generateAPACitation($lastName, $initials, $title, $section, $publication_date, $doi, $url);
        
        case 'mla':
            return generateMLACitation($author, $title, $section, $publication_date, $url);
        
        case 'chicago':
            return generateChicagoCitation($author, $title, $section, $publication_date, $url);
        
        case 'harvard':
            return generateHarvardCitation($lastName, $initials, $title, $section, $publication_date, $url);
        
        case 'ieee':
            return generateIEEECitation($author, $title, $section, $publication_date, $url);
        
        default:
            return generateAPACitation($lastName, $initials, $title, $section, $publication_date, $doi, $url);
    }
}

/**
 * Generate citation for a research project
 * 
 * @param array $research Research data array
 * @param string $format Citation format
 * @return string Formatted citation
 */
function generateResearchCitation($research, $format = 'apa') {
    $format = strtolower($format);
    
    // Get creator name
    $author = $research['creator_name'] ?? 'Unknown Author';
    $title = $research['title'] ?? 'Untitled';
    $publication_date = $research['publication_date'] ?? $research['created_at'] ?? date('Y');
    $doi = $research['doi'] ?? '';
    $category = $research['category'] ?? '';
    
    // Parse author name
    $authorParts = explode(' ', trim($author));
    $lastName = end($authorParts);
    $firstName = !empty($authorParts[0]) ? $authorParts[0] : '';
    $initials = !empty($firstName) ? substr($firstName, 0, 1) . '.' : '';
    
    switch ($format) {
        case 'apa':
            return generateAPACitation($lastName, $initials, $title, $category, $publication_date, $doi, '');
        
        case 'mla':
            return generateMLACitation($author, $title, $category, $publication_date, '');
        
        case 'chicago':
            return generateChicagoCitation($author, $title, $category, $publication_date, '');
        
        case 'harvard':
            return generateHarvardCitation($lastName, $initials, $title, $category, $publication_date, '');
        
        case 'ieee':
            return generateIEEECitation($author, $title, $category, $publication_date, '');
        
        default:
            return generateAPACitation($lastName, $initials, $title, $category, $publication_date, $doi, '');
    }
}

/**
 * Generate APA format citation
 */
function generateAPACitation($lastName, $initials, $title, $publisher, $year, $doi = '', $url = '') {
    $year = date('Y', strtotime($year));
    $citation = $lastName . ', ' . $initials . ' ' . $year . '. ';
    $citation .= $title . '. ';
    
    if (!empty($publisher)) {
        $citation .= $publisher . '. ';
    }
    
    if (!empty($doi)) {
        $citation .= 'https://doi.org/' . $doi;
    } elseif (!empty($url)) {
        $citation .= $url;
    }
    
    return trim($citation);
}

/**
 * Generate MLA format citation
 */
function generateMLACitation($author, $title, $publisher, $year, $url = '') {
    $year = date('Y', strtotime($year));
    $citation = $author . '. ';
    $citation .= '"' . $title . '." ';
    
    if (!empty($publisher)) {
        $citation .= $publisher . ', ';
    }
    
    $citation .= $year . '. ';
    
    if (!empty($url)) {
        $citation .= $url;
    }
    
    return trim($citation);
}

/**
 * Generate Chicago format citation
 */
function generateChicagoCitation($author, $title, $publisher, $year, $url = '') {
    $year = date('Y', strtotime($year));
    $citation = $author . '. ';
    $citation .= $title . '. ';
    
    if (!empty($publisher)) {
        $citation .= $publisher . ', ';
    }
    
    $citation .= $year . '. ';
    
    if (!empty($url)) {
        $citation .= $url;
    }
    
    return trim($citation);
}

/**
 * Generate Harvard format citation
 */
function generateHarvardCitation($lastName, $initials, $title, $publisher, $year, $url = '') {
    $year = date('Y', strtotime($year));
    $citation = $lastName . ', ' . $initials . ' ' . $year . '. ';
    $citation .= $title . '. ';
    
    if (!empty($publisher)) {
        $citation .= $publisher . '. ';
    }
    
    if (!empty($url)) {
        $citation .= 'Available at: ' . $url;
    }
    
    return trim($citation);
}

/**
 * Generate IEEE format citation
 */
function generateIEEECitation($author, $title, $publisher, $year, $url = '') {
    $year = date('Y', strtotime($year));
    $citation = $author . ', ';
    $citation .= '"' . $title . '," ';
    
    if (!empty($publisher)) {
        $citation .= $publisher . ', ';
    }
    
    $citation .= $year . '. ';
    
    if (!empty($url)) {
        $citation .= '[Online]. Available: ' . $url;
    }
    
    return trim($citation);
}

