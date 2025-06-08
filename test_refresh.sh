#!/bin/bash

# Configuration
API_URL="https://127.0.0.1:8002"
EMAIL="test@example.com"
PASSWORD="test123"

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "🔍 Test du rafraîchissement du token JWT"
echo "--------------------------------------"

# Étape 1: Authentification initiale
echo -e "\n${GREEN}Étape 1:${NC} Authentification initiale"
AUTH_RESPONSE=$(curl -s -k -X POST "$API_URL/api/login_check" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

TOKEN=$(echo $AUTH_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
REFRESH_TOKEN=$(echo $AUTH_RESPONSE | grep -o '"refresh_token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ] || [ -z "$REFRESH_TOKEN" ]; then
    echo "❌ Erreur d'authentification"
    echo "Réponse: $AUTH_RESPONSE"
    exit 1
else
    echo "✅ Authentification réussie"
    echo "Token JWT obtenu"
    echo "Refresh token obtenu"
fi

# Étape 2: Test d'accès à une route protégée avec le token initial
echo -e "\n${GREEN}Étape 2:${NC} Test d'accès avec le token initial"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/protected")
if [[ $RESPONSE == *"Vous avez acc"* ]]; then
    echo "✅ Accès réussi avec le token initial"
else
    echo "❌ Erreur d'accès avec le token initial"
    echo "Réponse: $RESPONSE"
fi

# Étape 3: Rafraîchissement du token
echo -e "\n${GREEN}Étape 3:${NC} Rafraîchissement du token"
REFRESH_RESPONSE=$(curl -s -k -X POST "$API_URL/api/token/refresh" \
    -H "Content-Type: application/json" \
    -d "{\"refresh_token\":\"$REFRESH_TOKEN\"}")

NEW_TOKEN=$(echo $REFRESH_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$NEW_TOKEN" ]; then
    echo "❌ Erreur lors du rafraîchissement du token"
    echo "Réponse: $REFRESH_RESPONSE"
    exit 1
elif [ "$NEW_TOKEN" = "$TOKEN" ]; then
    echo "❌ Erreur : le nouveau token est identique à l'ancien"
    echo "Réponse: $REFRESH_RESPONSE"
    exit 1
else
    echo "✅ Token rafraîchi avec succès"
fi

# Étape 4: Test d'accès avec le nouveau token
echo -e "\n${GREEN}Étape 4:${NC} Test d'accès avec le nouveau token"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $NEW_TOKEN" "$API_URL/api/protected")
if [[ $RESPONSE == *"Vous avez acc"* ]]; then
    echo "✅ Accès réussi avec le nouveau token"
else
    echo "❌ Erreur d'accès avec le nouveau token"
    echo "Réponse: $RESPONSE"
fi

# Étape 5: Test d'accès avec l'ancien token
echo -e "\n${GREEN}Étape 5:${NC} Test d'accès avec l'ancien token"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/protected")
if [[ $RESPONSE == *"Vous avez acc"* ]]; then
    echo "⚠️  Attention : l'ancien token est toujours valide"
    echo "Réponse: $RESPONSE"
else
    echo "✅ Accès refusé avec l'ancien token (comportement attendu)"
fi

echo -e "\n${GREEN}Tests terminés${NC}" 