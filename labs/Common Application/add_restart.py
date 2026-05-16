import yaml

with open('docker-compose.yml', 'r') as f:
    data = yaml.safe_load(f)

for service in data['services']:
    data['services'][service]['restart'] = 'unless-stopped'

with open('docker-compose.yml', 'w') as f:
    yaml.dump(data, f, sort_keys=False)
