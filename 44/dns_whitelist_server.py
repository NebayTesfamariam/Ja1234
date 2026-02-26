#!/usr/bin/env python3
"""
DNS Whitelist-Only Server
Resolves domains ONLY if they are in the whitelist for the device
Returns NXDOMAIN for all other domains
"""

import socket
import struct
import requests
import time
import threading
from collections import defaultdict
from datetime import datetime, timedelta

# Configuration
DNS_PORT = 53
# Auto-detect API URL based on environment
import os
if os.getenv('HTTP_HOST') and 'ja1234.com' in os.getenv('HTTP_HOST', ''):
    API_BASE_URL = "https://ja1234.com/api"  # Production
else:
    API_BASE_URL = "http://localhost/44/api"  # Development
# Secret for DNS server to fetch whitelist (set same as PHP env DNS_INTERNAL_KEY)
DNS_INTERNAL_KEY = os.getenv('DNS_INTERNAL_KEY', '')
CACHE_TTL = 15  # Cache whitelist for 15 seconds
VPN_SUBNET = "10.10.0.0/24"

# Cache for whitelists per device
whitelist_cache = {}
cache_timestamps = {}


def get_device_id_from_ip(client_ip):
    """
    Get device_id from VPN client IP address
    Calls API: /api/get_device_by_ip.php?ip=CLIENT_IP
    """
    try:
        response = requests.get(
            f"{API_BASE_URL}/get_device_by_ip.php",
            params={"ip": client_ip},
            timeout=2
        )
        if response.status_code == 200:
            data = response.json()
            if data.get('found') and data.get('device_id'):
                return data['device_id']
    except Exception as e:
        print(f"Error getting device_id for IP {client_ip}: {e}")
    return None


def get_whitelist_for_device(device_id):
    """
    Get whitelist for device from API
    Returns list of domains or empty list
    """
    # Check cache first
    if device_id in whitelist_cache:
        if device_id in cache_timestamps:
            age = time.time() - cache_timestamps[device_id]
            if age < CACHE_TTL:
                return whitelist_cache[device_id]
    
    # Fetch from API (with internal key so DNS server can get whitelist without user auth)
    try:
        headers = {}
        if DNS_INTERNAL_KEY:
            headers['X-DNS-Internal-Key'] = DNS_INTERNAL_KEY
        response = requests.get(
            f"{API_BASE_URL}/get_whitelist.php",
            params={"device_id": device_id},
            headers=headers,
            timeout=2
        )
        
        if response.status_code == 200:
            whitelist = response.json()
            # API returns array of domains: ["wikipedia.org", "google.com"]
            if isinstance(whitelist, list):
                # Normalize domains (lowercase, no www)
                normalized = []
                for domain in whitelist:
                    domain = domain.lower().strip()
                    domain = domain.lstrip('www.')
                    if domain:
                        normalized.append(domain)
                
                # Update cache
                whitelist_cache[device_id] = normalized
                cache_timestamps[device_id] = time.time()
                return normalized
    except Exception as e:
        print(f"Error fetching whitelist for device {device_id}: {e}")
        # Fail-safe: return empty list (block everything)
    
    # Fail-safe: return empty list
    return []


# Pornographic domain patterns (permanent block – English, French, and more)
PORN_PATTERNS = [
    # --- English ---
    'porn', 'xxx', 'sex', 'adult', 'nude', 'naked', 'erotic', 'erotica',
    'hardcore', 'fetish', 'bdsm', 'lesbian', 'gay', 'milf', 'teen',
    'anal', 'oral', 'blowjob', 'cumshot', 'orgasm', 'masturbat',
    'escort', 'hooker', 'prostitute', 'camgirl', 'webcam',
    'pornhub', 'xvideos', 'xhamster', 'redtube', 'youporn', 'tube8',
    'xnxx', 'chaturbate', 'livejasmin', 'onlyfans',
    'phncdn', 'phcdn', 'xvcdn', 'xhcdn', 'rtcdn', 'ypcdn',
    'porncdn', 'adultcdn', 'sexcdn', 'xxxcdn',
    'brazzers', 'realitykings', 'bangbros', 'naughtyamerica',
    'vixen', 'tushy', 'blacked', 'deeper', 'kink', 'hardx',
    'amateur', 'threesome', 'gangbang', 'rough',
    'spankwire', 'keezmovies', 'extremetube', 'sunporno', '4tube',
    'pornmd', 'porn300', 'porn555', 'myfreecams', 'cam4', 'streamate',
    'justforfans', 'manyvids', 'fansly', 'fapster', 'eporner',
    'beeg', 'tnaflix', 'pornone', 'pornrox', 'pornhat', 'pornid',
    'drtuber', 'nuvid', 'empflix', 'pornicom', 'pornoxo',
    'nsfw', '18+', 'adult-content', 'mature', 'explicit',
    # --- French ---
    'porno', 'sexe', 'nu', 'nue', 'nus', 'nues', 'érotique', 'erotique',
    'hardcore', 'fétichisme', 'fetichisme', 'escorte', 'prostituée', 'prostituee',
    'sodomie', 'sodom', 'pénis', 'penis', 'vagin', 'seins', 'cul',
    'pornographique', 'pornographiques', 'adulte', 'adultes',
    'xhamster', 'youporn', 'redtube', 'pornhub', 'xvideos',
    'jacquieetmichel', 'jacquie', 'michel', 'coquin', 'coquine',
    'nu.fr', 'sexe.fr', 'porno.fr', 'video-porno', 'vidéo-porno',
    'film-porno', 'film-x', 'video-x', 'vidéo-x',
    'libertin', 'libertine', 'échangiste', 'echangiste',
    'rencontre-adulte', 'site-adulte', 'contenu-adulte',
    # --- Dutch ---
    'porno', 'seks', 'naakt', 'erotisch', 'prostituee',
    # --- German ---
    'porno', 'nackt', 'erotisch', 'fetisch', 'prostituierte',
    # --- Spanish ---
    'porno', 'sexo', 'desnudo', 'erótico', 'erotico', 'fetiche', 'prostituta',
    # --- Italian ---
    'porno', 'sesso', 'nudo', 'erotico', 'feticismo', 'prostituta',
    # --- Portuguese ---
    'porno', 'sexo', 'nu', 'nua', 'erótico', 'erotico', 'prostituta',
    # --- More generic / international ---
    'camshow', 'camsite', 'livecam', 'adultcam', 'freecam',
    'pornstar', 'porn-star', 'adult-video', 'adultvideo',
    'hentai', 'rule34', 'e621', 'furry-porn',
    'dating-adult', 'hookup', 'one-night-stand',
    'stripclub', 'strip-club', 'peepshow', 'peep-show',
]
PORN_TLDS = ['.xxx', '.adult', '.sex', '.porn', '.porno']

def is_pornographic_domain(domain):
    """Check if domain is pornographic - PERMANENT BLOCK"""
    domain_lower = domain.lower()
    
    # Check TLD
    for tld in PORN_TLDS:
        if tld in domain_lower:
            return True
    
    # Check patterns
    for pattern in PORN_PATTERNS:
        if pattern in domain_lower:
            return True
    
    return False

def is_domain_in_whitelist(domain, whitelist):
    """
    Check if domain is in whitelist
    PERMANENT BLOCK: Pornographic domains are ALWAYS blocked, even if in whitelist
    """
    domain = domain.lower().strip()
    domain = domain.lstrip('www.')
    domain = domain.rstrip('.')
    
    # PERMANENT BLOCK: Pornographic domains are NEVER allowed
    if is_pornographic_domain(domain):
        return False
    
    # Exact match
    if domain in whitelist:
        return True
    
    # Subdomain match
    for whitelisted_domain in whitelist:
        whitelisted_domain = whitelisted_domain.lower().strip().rstrip('.')
        
        if domain == whitelisted_domain:
            return True
        
        if domain.endswith('.' + whitelisted_domain):
            return True
    
    return False


def parse_dns_query(data):
    """
    Parse DNS query to extract domain name
    Returns (domain, query_id) or (None, None)
    """
    try:
        # DNS header is 12 bytes
        if len(data) < 12:
            return None, None
        
        # Extract query ID (first 2 bytes)
        query_id = struct.unpack('!H', data[0:2])[0]
        
        # Parse domain name (starts at byte 12)
        pos = 12
        domain_parts = []
        
        while pos < len(data) and data[pos] != 0:
            length = data[pos]
            if length == 0:
                break
            if length > 63:  # Compression pointer
                break
            pos += 1
            if pos + length > len(data):
                break
            part = data[pos:pos+length].decode('utf-8', errors='ignore')
            domain_parts.append(part)
            pos += length
        
        if domain_parts:
            domain = '.'.join(domain_parts)
            return domain, query_id
        
    except Exception as e:
        print(f"Error parsing DNS query: {e}")
    
    return None, None


def create_nxdomain_response(query_data, query_id):
    """
    Create NXDOMAIN (domain not found) response
    """
    try:
        response = bytearray(query_data)
        
        # Set QR bit (response) and RCODE to NXDOMAIN (3)
        # Byte 2: flags high byte
        # Byte 3: flags low byte (RCODE in lower 4 bits)
        response[2] = 0x81  # QR=1, Opcode=0
        response[3] = 0x83  # AA=0, TC=0, RD=1, RA=0, Z=0, RCODE=3 (NXDOMAIN)
        
        # Set query ID
        response[0:2] = struct.pack('!H', query_id)
        
        # Set ANCOUNT, NSCOUNT, ARCOUNT to 0
        response[6:8] = b'\x00\x00'  # ANCOUNT
        response[8:10] = b'\x00\x00'  # NSCOUNT
        response[10:12] = b'\x00\x00'  # ARCOUNT
        
        return bytes(response)
    except Exception as e:
        print(f"Error creating NXDOMAIN response: {e}")
        return None


def resolve_domain_upstream(query_data, domain):
    """
    Resolve domain using upstream DNS (8.8.8.8)
    Returns DNS response or None
    """
    try:
        upstream = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        upstream.settimeout(3)
        upstream.sendto(query_data, ('8.8.8.8', 53))
        response, _ = upstream.recvfrom(512)
        upstream.close()
        return response
    except Exception as e:
        print(f"Error resolving domain {domain} upstream: {e}")
        return None


def handle_dns_query(data, client_addr):
    """
    Handle incoming DNS query
    """
    client_ip = client_addr[0]
    
    # Parse query
    domain, query_id = parse_dns_query(data)
    if not domain:
        return None
    
    print(f"[{datetime.now().strftime('%H:%M:%S')}] DNS query: {domain} from {client_ip}")
    
    # Get device_id from client IP
    device_id = get_device_id_from_ip(client_ip)
    if not device_id:
        print(f"  → No device_id found for IP {client_ip} - returning NXDOMAIN")
        return create_nxdomain_response(data, query_id)
    
    # Get whitelist for device
    whitelist = get_whitelist_for_device(device_id)
    
    # Check if domain is in whitelist
    if not whitelist:
        print(f"  → Empty whitelist for device {device_id} - returning NXDOMAIN")
        return create_nxdomain_response(data, query_id)
    
    if not is_domain_in_whitelist(domain, whitelist):
        print(f"  → Domain {domain} NOT in whitelist - returning NXDOMAIN")
        return create_nxdomain_response(data, query_id)
    
    # Domain is in whitelist - resolve it
    print(f"  → Domain {domain} is in whitelist - resolving")
    response = resolve_domain_upstream(data, domain)
    if response:
        # Update query ID in response
        response = bytearray(response)
        response[0:2] = struct.pack('!H', query_id)
        return bytes(response)
    else:
        # If upstream resolution fails, return NXDOMAIN
        print(f"  → Upstream resolution failed - returning NXDOMAIN")
        return create_nxdomain_response(data, query_id)


def run_dns_server():
    """
    Run DNS server on port 53
    """
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.bind(('0.0.0.0', DNS_PORT))
        print(f"DNS Whitelist Server started on port {DNS_PORT}")
        print(f"API Base URL: {API_BASE_URL}")
        print(f"Cache TTL: {CACHE_TTL} seconds")
        print("Waiting for DNS queries...")
        
        while True:
            try:
                data, addr = sock.recvfrom(512)
                response = handle_dns_query(data, addr)
                if response:
                    sock.sendto(response, addr)
            except Exception as e:
                print(f"Error handling DNS query: {e}")
                
    except PermissionError:
        print("ERROR: Permission denied. DNS server must run as root (port 53 requires root)")
        print("Run with: sudo python3 dns_whitelist_server.py")
    except Exception as e:
        print(f"Fatal error: {e}")


if __name__ == "__main__":
    run_dns_server()
