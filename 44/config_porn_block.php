<?php
/**
 * Permanent Pornographic Domain Blocking
 * Prevents pornographic domains from being added to whitelist
 * Works in all languages - detects porn domains automatically
 */

// List of known pornographic domain patterns (English, French, and more)
$PORN_DOMAIN_PATTERNS = [
    // English
    'porn', 'xxx', 'sex', 'adult', 'nude', 'naked', 'erotic', 'erotica',
    'hardcore', 'fetish', 'bdsm', 'lesbian', 'gay', 'milf', 'teen',
    'anal', 'oral', 'blowjob', 'cumshot', 'orgasm', 'masturbat',
    'escort', 'hooker', 'prostitute', 'camgirl', 'webcam',
    'pornhub', 'xvideos', 'xhamster', 'redtube', 'youporn', 'tube8',
    'spankwire', 'keezmovies', 'extremetube', 'sunporno', '4tube',
    'pornmd', 'porn300', 'porn555', 'xnxx', 'xvideo', 'chaturbate',
    'livejasmin', 'myfreecams', 'cam4', 'streamate', 'onlyfans',
    'justforfans', 'manyvids', 'fansly', 'fapster', 'eporner',
    'beeg', 'tnaflix', 'pornone', 'pornrox', 'pornhat', 'pornid',
    'drtuber', 'nuvid', 'empflix', 'pornicom', 'pornoxo',
    'phncdn', 'phcdn', 'xvcdn', 'xhcdn', 'rtcdn', 'ypcdn',
    'porncdn', 'adultcdn', 'sexcdn', 'xxxcdn',
    'brazzers', 'realitykings', 'bangbros', 'naughtyamerica',
    'vixen', 'tushy', 'blacked', 'deeper', 'kink', 'hardx',
    'amateur', 'threesome', 'gangbang', 'rough',
    'nsfw', '18+', 'adult-content', 'mature', 'explicit',
    'camshow', 'camsite', 'livecam', 'adultcam', 'freecam',
    'pornstar', 'porn-star', 'adult-video', 'adultvideo',
    'hentai', 'rule34', 'e621', 'dating-adult', 'hookup',
    'stripclub', 'strip-club', 'peepshow', 'peep-show',
    // French
    'porno', 'sexe', 'nu', 'nue', 'nus', 'nues', 'érotique', 'erotique',
    'fétichisme', 'fetichisme', 'escorte', 'prostituée', 'prostituee',
    'sodomie', 'sodom', 'pénis', 'penis', 'vagin', 'seins', 'cul',
    'pornographique', 'pornographiques', 'adulte', 'adultes',
    'jacquieetmichel', 'jacquie', 'michel', 'coquin', 'coquine',
    'video-porno', 'vidéo-porno', 'film-porno', 'film-x', 'video-x', 'vidéo-x',
    'libertin', 'libertine', 'échangiste', 'echangiste',
    'rencontre-adulte', 'site-adulte', 'contenu-adulte',
    // Dutch
    'porno', 'seks', 'naakt', 'erotisch', 'prostituee',
    // German
    'porno', 'nackt', 'erotisch', 'fetisch', 'prostituierte',
    // Spanish
    'porno', 'sexo', 'desnudo', 'erótico', 'erotico', 'fetiche', 'prostituta',
    // Italian
    'porno', 'sesso', 'nudo', 'erotico', 'feticismo', 'prostituta',
    // Portuguese
    'porno', 'sexo', 'nu', 'nua', 'erótico', 'erotico', 'prostituta',
];

// Known pornographic TLDs
$PORN_TLDS = [
    '.xxx', '.adult', '.sex', '.porn'
];

/**
 * Check if domain is pornographic
 * Works in all languages
 */
function is_pornographic_domain(string $domain): bool {
    global $PORN_DOMAIN_PATTERNS, $PORN_TLDS;
    
    // Ensure arrays are loaded
    if (!isset($PORN_DOMAIN_PATTERNS) || !is_array($PORN_DOMAIN_PATTERNS)) {
        $PORN_DOMAIN_PATTERNS = [
            'porn', 'xxx', 'sex', 'adult', 'nude', 'naked', 'erotic', 'erotica',
            'hardcore', 'fetish', 'bdsm', 'lesbian', 'gay', 'milf', 'teen',
            'anal', 'oral', 'blowjob', 'cumshot', 'orgasm', 'masturbat',
            'escort', 'hooker', 'prostitute', 'camgirl', 'webcam',
            'pornhub', 'xvideos', 'xhamster', 'redtube', 'youporn', 'tube8',
            'spankwire', 'keezmovies', 'extremetube', 'sunporno', '4tube',
            'porn', 'xnxx', 'xvideo', 'pornmd', 'porn300', 'porn555',
            'chaturbate', 'livejasmin', 'myfreecams', 'cam4', 'streamate',
            'onlyfans', 'justforfans', 'manyvids',
            'phncdn', 'phcdn', 'xvcdn', 'xhcdn', 'rtcdn', 'ypcdn',
            'porncdn', 'adultcdn', 'sexcdn', 'xxxcdn',
            'brazzers', 'realitykings', 'bangbros', 'naughtyamerica',
            'vixen', 'tushy', 'blacked', 'deeper', 'kink', 'hardx',
            'amateur', 'threesome', 'gangbang', 'rough',
            'porno', 'seks', 'naakt',
            'porno', 'sex', 'nackt',
            'porno', 'sexe', 'nu',
            'porno', 'sexo', 'desnudo',
            'porno', 'sesso', 'nudo',
            'nsfw', '18+', 'adult-content', 'mature', 'explicit'
        ];
    }
    
    if (!isset($PORN_TLDS) || !is_array($PORN_TLDS)) {
        $PORN_TLDS = ['.xxx', '.adult', '.sex', '.porn'];
    }
    
    $domain_lower = strtolower($domain);
    
    // Check TLD
    foreach ($PORN_TLDS as $tld) {
        if (strpos($domain_lower, $tld) !== false) {
            return true;
        }
    }
    
    // Check domain patterns
    foreach ($PORN_DOMAIN_PATTERNS as $pattern) {
        if (strpos($domain_lower, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Normalize and check domain
 * Returns false if pornographic
 */
function validate_domain_for_whitelist(string $domain): array {
    // Normalize domain (same as normalize_domain function)
    $domain = strtolower(trim($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = explode('/', $domain)[0];
    $domain = explode('?', $domain)[0];
    $domain = ltrim($domain, 'www.');
    $domain = trim($domain, '.');
    
    if (empty($domain)) {
        return ['valid' => false, 'reason' => 'Empty domain'];
    }
    
    if (is_pornographic_domain($domain)) {
        return [
            'valid' => false, 
            'reason' => 'Pornographic domain detected - permanently blocked',
            'blocked' => true
        ];
    }
    
    return ['valid' => true, 'domain' => $domain];
}

/**
 * Remove all pornographic domains from whitelist
 * Permanent cleanup
 */
function remove_pornographic_domains_from_whitelist($conn): int {
    $removed = 0;
    
    try {
        $stmt = $conn->query("SELECT id, domain FROM whitelist WHERE enabled = 1");
        $domains = $stmt->fetch_all(MYSQLI_ASSOC);
        
        foreach ($domains as $row) {
            if (is_pornographic_domain($row['domain'])) {
                $delete_stmt = $conn->prepare("DELETE FROM whitelist WHERE id = ?");
                $delete_stmt->bind_param("i", $row['id']);
                $delete_stmt->execute();
                $removed++;
            }
        }
    } catch (Exception $e) {
        error_log("Error removing pornographic domains: " . $e->getMessage());
    }
    
    return $removed;
}
