#!/bin/bash

# Wait for Vault to be ready
kubectl wait --for=condition=Ready pod/vault-0 -n vault

# Initialize Vault (save the keys and token securely)
echo "Initializing Vault..."
kubectl exec -it vault-0 -n vault -- vault operator init

# Prompt for unseal keys
echo "Please enter the first unseal key:"
read -s UNSEAL_KEY1
echo "Please enter the second unseal key:"
read -s UNSEAL_KEY2
echo "Please enter the third unseal key:"
read -s UNSEAL_KEY3

# Unseal Vault
echo "Unsealing Vault..."
kubectl exec -it vault-0 -n vault -- vault operator unseal $UNSEAL_KEY1
kubectl exec -it vault-0 -n vault -- vault operator unseal $UNSEAL_KEY2
kubectl exec -it vault-0 -n vault -- vault operator unseal $UNSEAL_KEY3

# Prompt for root token
echo "Please enter the root token:"
read -s ROOT_TOKEN

# Configure Vault
echo "Configuring Vault..."
kubectl exec -it vault-0 -n vault -- sh -c "VAULT_TOKEN=$ROOT_TOKEN vault auth enable kubernetes"

kubectl exec -it vault-0 -n vault -- sh -c "VAULT_TOKEN=$ROOT_TOKEN vault write auth/kubernetes/config \
    kubernetes_host=\"https://\$KUBERNETES_SERVICE_HOST:\$KUBERNETES_SERVICE_PORT\" \
    token_reviewer_jwt=\"\$(cat /var/run/secrets/kubernetes.io/serviceaccount/token)\" \
    kubernetes_ca_cert=\"\$(cat /var/run/secrets/kubernetes.io/serviceaccount/ca.crt)\" \
    issuer=\"https://kubernetes.default.svc.cluster.local\""

# Enable KV secrets engine
kubectl exec -it vault-0 -n vault -- sh -c "VAULT_TOKEN=$ROOT_TOKEN vault secrets enable -path=chat-app kv-v2"

echo "Vault setup complete!"