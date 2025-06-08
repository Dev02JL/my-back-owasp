#!/bin/bash

# Configuration
API_URL="https://127.0.0.1:8002"
EMAIL="test@example.com"
PASSWORD="test123"

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "🔍 Test de l'authentification et des routes protégées"
echo "------------------------------------------------"

# Test de la route publique
echo -e "\n${GREEN}Test 1:${NC} Accès à la route publique"
RESPONSE=$(curl -s -k "$API_URL/api/public")
if [[ $RESPONSE == *"Cette route est publique"* ]]; then
    echo "✅ Route publique accessible"
else
    echo "❌ Erreur: Route publique inaccessible"
    echo "Réponse: $RESPONSE"
fi

# Test de la route protégée sans authentification
echo -e "\n${GREEN}Test 2:${NC} Tentative d'accès à une route protégée sans authentification"
RESPONSE=$(curl -s -k "$API_URL/api/protected")
if [[ $RESPONSE == *"JWT Token not found"* ]]; then
    echo "✅ Accès refusé comme prévu"
else
    echo "❌ Erreur: Accès inattendu à la route protégée"
    echo "Réponse: $RESPONSE"
fi

# Test de l'authentification
echo -e "\n${GREEN}Test 3:${NC} Authentification avec les identifiants"
RESPONSE=$(curl -s -k -X POST -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
    "$API_URL/api/login_check")

TOKEN=$(echo $RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Erreur d'authentification"
    echo "Réponse: $RESPONSE"
    exit 1
else
    echo "✅ Authentification réussie"
fi

# Test de la route protégée avec authentification
echo -e "\n${GREEN}Test 4:${NC} Accès à une route protégée avec authentification"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/protected")
if [[ $RESPONSE == *"Vous avez acc"* ]]; then
    echo "✅ Route protégée accessible"
else
    echo "❌ Erreur: Route protégée inaccessible"
    echo "Réponse: $RESPONSE"
fi

# Test de la route admin avec un utilisateur non-admin
echo -e "\n${GREEN}Test 5:${NC} Tentative d'accès à la route admin avec un utilisateur non-admin"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/admin")
if [[ $RESPONSE == *"Access Denied"* ]]; then
    echo "✅ Accès refusé comme prévu"
else
    echo "❌ Erreur: Accès inattendu à la route admin"
    echo "Réponse: $RESPONSE"
fi

# Nettoyage
rm -f cookies.txt

echo -e "\n${GREEN}Tests terminés${NC}" 