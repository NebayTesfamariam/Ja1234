#!/usr/bin/env python3
"""
VPN/DNS Filter Server Example
Werkt met huidige API voor filtering in alle apps
"""

import socket
import requests
import json
from threading import Thread
import time

# API Configuration
API_BASE_URL = "https://your-domain.com/api"  # Vervang met jouw API URL
API_TOKEN = "YOUR_API_TOKEN"  # Optioneel: voor API authenticatie

# VPN Configuration
VPN_SERVER_IP = "10.10.0.1"
VPN_SUBNET = "10.10.0.0/24"
DNS_PORT = 53
VPN_PORT = 51820

# Device IP to Device ID mapping
# In productie: haal dit op uit database via API
DEVICE_IP_MAP = {
    "10.10.0.2": 1,  # Device ID 1
    "10.10.0.3": 2,  # Device ID 2
    # etc.
}


class VPNFilter:
    """VPN Filter die huidige API gebruikt"""
    
    def __init__(self, api_base_url, api_token=None):
        self.api_base_url = api_base_url
        self.api_token = api_token
        self.blocklist_cache = {}
        self.cache_ttl = 10  # 10 seconden cache
    
    def get_device_id_from_ip(self, client_ip):
        """Haal device ID op basis van client IP"""
        # Check direct mapping
        if client_ip in DEVICE_IP_MAP:
            return DEVICE_IP_MAP[client_ip]
        
        # Of haal op via API
        try:
            response = requests.get(
                f"{self.api_base_url}/get_device_by_ip.php",
                params={"ip": client_ip},
                headers={"Authorization": f"Bearer {self.api_token}"} if self.api_token else {},
                timeout=2
            )
            if response.ok:
                data = response.json()
                return data.get('device_id')
        except:
            pass
        
        return None
    
    def should_block(self, device_id, domain, url=None):
        """Check of domain geblokkeerd moet worden via API"""
        if not device_id:
            return True  # Fail-safe: block if no device ID
        
        # Check cache first
        cache_key = f"{device_id}_{domain}"
        if cache_key in self.blocklist_cache:
            cached = self.blocklist_cache[cache_key]
            if time.time() - cached['time'] < self.cache_ttl:
                return cached['blocked']
        
        try:
            params = {
                'device_id': device_id,
                'domain': domain
            }
            if url:
                params['url'] = url
            
            headers = {}
            if self.api_token:
                headers['Authorization'] = f'Bearer {self.api_token}'
            
            response = requests.get(
                f'{self.api_base_url}/check_domain.php',
                params=params,
                headers=headers,
                timeout=2
            )
            
            if response.ok:
                data = response.json()
                blocked = not data.get('allowed', True)
                
                # Cache result
                self.blocklist_cache[cache_key] = {
                    'blocked': blocked,
                    'time': time.time()
                }
                
                return blocked
        except Exception as e:
            print(f"Error checking domain {domain}: {e}")
            # On error, block for safety (fail-safe)
            return True
        
        # Default: block for safety
        return True
    
    def filter_request(self, client_ip, domain, url=None):
        """Filter incoming request"""
        device_id = self.get_device_id_from_ip(client_ip)
        
        if not device_id:
            print(f"No device ID found for IP {client_ip} - blocking")
            return False  # Block
        
        should_block = self.should_block(device_id, domain, url)
        
        if should_block:
            print(f"BLOCKED: {domain} for device {device_id}")
        else:
            print(f"ALLOWED: {domain} for device {device_id}")
        
        return not should_block  # Return True if allowed, False if blocked


class DNSServer:
    """DNS Server die VPN Filter gebruikt"""
    
    def __init__(self, vpn_filter):
        self.filter = vpn_filter
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.socket.bind(('0.0.0.0', DNS_PORT))
    
    def handle_query(self, data, addr):
        """Handle DNS query"""
        try:
            # Parse DNS query (simplified)
            domain = self.parse_dns_query(data)
            client_ip = addr[0]
            
            if not domain:
                return None
            
            # Check if should block
            if not self.filter.filter_request(client_ip, domain):
                # Block: return NXDOMAIN (domain not found)
                return self.create_nxdomain_response(data)
            
            # Allow: resolve domain normally
            return self.resolve_domain(data, domain)
            
        except Exception as e:
            print(f"Error handling DNS query: {e}")
            return None
    
    def parse_dns_query(self, data):
        """Parse DNS query to extract domain"""
        # Simplified DNS parsing
        # In productie: gebruik dnslib of andere DNS library
        try:
            # Skip header (12 bytes)
            pos = 12
            domain_parts = []
            
            while pos < len(data) and data[pos] != 0:
                length = data[pos]
                pos += 1
                if length == 0:
                    break
                part = data[pos:pos+length].decode('utf-8')
                domain_parts.append(part)
                pos += length
            
            if domain_parts:
                return '.'.join(domain_parts)
        except:
            pass
        
        return None
    
    def create_nxdomain_response(self, query_data):
        """Create NXDOMAIN response (domain not found)"""
        # Simplified: return NXDOMAIN response
        # In productie: gebruik dnslib voor correcte DNS responses
        response = bytearray(query_data)
        # Set response code to NXDOMAIN (3)
        response[2] = (response[2] & 0xF0) | 0x03
        response[3] = response[3] | 0x80  # Set QR bit (response)
        return bytes(response)
    
    def resolve_domain(self, query_data, domain):
        """Resolve domain normally"""
        # Forward to upstream DNS server (8.8.8.8)
        try:
            upstream = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            upstream.settimeout(2)
            upstream.sendto(query_data, ('8.8.8.8', 53))
            response, _ = upstream.recvfrom(512)
            upstream.close()
            return response
        except:
            return None
    
    def run(self):
        """Run DNS server"""
        print(f"DNS Server running on port {DNS_PORT}")
        while True:
            try:
                data, addr = self.socket.recvfrom(512)
                response = self.handle_query(data, addr)
                if response:
                    self.socket.sendto(response, addr)
            except Exception as e:
                print(f"DNS Server error: {e}")


class VPNServer:
    """VPN Server die VPN Filter gebruikt"""
    
    def __init__(self, vpn_filter):
        self.filter = vpn_filter
        # In productie: gebruik WireGuard, OpenVPN, etc.
    
    def filter_packet(self, packet, client_ip):
        """Filter VPN packet"""
        # Extract domain/URL from packet
        # In productie: parse HTTP/HTTPS requests
        domain = self.extract_domain_from_packet(packet)
        
        if domain:
            return self.filter.filter_request(client_ip, domain)
        
        return True  # Allow if can't parse
    
    def extract_domain_from_packet(self, packet):
        """Extract domain from network packet"""
        # Simplified: in productie gebruik packet parsing library
        # For HTTP: parse Host header
        # For HTTPS: parse SNI (Server Name Indication)
        try:
            # Example: extract from HTTP request
            if b'Host:' in packet:
                host_line = packet[packet.find(b'Host:'):packet.find(b'\r\n', packet.find(b'Host:'))]
                domain = host_line.split(b':')[1].strip().decode('utf-8')
                return domain
        except:
            pass
        
        return None
    
    def run(self):
        """Run VPN server"""
        print(f"VPN Server running on port {VPN_PORT}")
        # In productie: implementeer WireGuard/OpenVPN server
        pass


def main():
    """Main function"""
    print("Starting VPN/DNS Filter Server...")
    print(f"API Base URL: {API_BASE_URL}")
    
    # Initialize filter
    filter = VPNFilter(API_BASE_URL, API_TOKEN)
    
    # Start DNS server
    dns_server = DNSServer(filter)
    dns_thread = Thread(target=dns_server.run, daemon=True)
    dns_thread.start()
    
    # Start VPN server (if needed)
    # vpn_server = VPNServer(filter)
    # vpn_thread = Thread(target=vpn_server.run, daemon=True)
    # vpn_thread.start()
    
    print("VPN/DNS Filter Server started!")
    print("Press Ctrl+C to stop")
    
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\nStopping server...")


if __name__ == "__main__":
    main()
