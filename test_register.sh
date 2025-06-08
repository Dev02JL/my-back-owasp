#!/bin/bash

# Configuration
API_URL="https://127.0.0.1:8002"
EMAIL="test@example.com"
PASSWORD="test12345" # 8 caractères

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "🔍 Test de l'inscription des utilisateurs"
echo "----------------------------------------"

# Test 1: Inscription avec des données valides
echo -e "\n${GREEN}Test 1:${NC} Inscription avec des données valides"
RESPONSE=$(curl -s -k -X POST "$API_URL/register" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

if [[ $RESPONSE == *"Cet email est d"* ]]; then
    echo "✅ Erreur attendue : email déjà utilisé"
else
    echo "❌ Erreur lors de l'inscription"
    echo "Réponse: $RESPONSE"
fi

# Test 2: Tentative d'inscription sans email
echo -e "\n${GREEN}Test 2:${NC} Tentative d'inscription sans email"
RESPONSE=$(curl -s -k -X POST "$API_URL/register" \
    -H "Content-Type: application/json" \
    -d "{\"password\":\"$PASSWORD\"}")

if [[ $RESPONSE == *"Email et mot de passe requis"* ]]; then
    echo "✅ Erreur attendue : email manquant"
else
    echo "❌ Erreur inattendue"
    echo "Réponse: $RESPONSE"
fi

# Test 3: Tentative d'inscription sans mot de passe
echo -e "\n${GREEN}Test 3:${NC} Tentative d'inscription sans mot de passe"
RESPONSE=$(curl -s -k -X POST "$API_URL/register" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\"}")

if [[ $RESPONSE == *"Email et mot de passe requis"* ]]; then
    echo "✅ Erreur attendue : mot de passe manquant"
else
    echo "❌ Erreur inattendue"
    echo "Réponse: $RESPONSE"
fi

# Test 4: Tentative d'inscription avec un email invalide
echo -e "\n${GREEN}Test 4:${NC} Tentative d'inscription avec un email invalide"
RESPONSE=$(curl -s -k -X POST "$API_URL/register" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"invalid-email\",\"password\":\"$PASSWORD\"}")

if [[ $RESPONSE == *"Format d'email invalide"* || $RESPONSE == *"Format d\u0027email invalide"* ]]; then
    echo "✅ Erreur attendue : email invalide"
else
    echo "❌ Erreur inattendue"
    echo "Réponse: $RESPONSE"
fi

# Test 5: Tentative d'inscription avec un mot de passe trop court
echo -e "\n${GREEN}Test 5:${NC} Tentative d'inscription avec un mot de passe trop court"
RESPONSE=$(curl -s -k -X POST "$API_URL/register" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"short\"}")

if [[ $RESPONSE == *"Le mot de passe doit contenir au moins 8 caract"* ]]; then
    echo "✅ Erreur attendue : mot de passe trop court"
else
    echo "❌ Erreur inattendue"
    echo "Réponse: $RESPONSE"
fi

echo -e "\n${GREEN}Tests terminés${NC}" 