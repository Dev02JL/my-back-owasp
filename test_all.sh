#!/bin/bash

set -e

scripts=(
  "test_auth.sh"
  "test_cart.sh"
  "test_products.sh"
  "test_refresh.sh"
  "test_register.sh"
  "test_reviews.sh"
)

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

all_passed=true

for script in "${scripts[@]}"; do
  echo -e "\n=============================="
  echo -e "Exécution de $script"
  echo -e "=============================="
  if bash "$script"; then
    echo -e "${GREEN}✔ $script : OK${NC}"
  else
    echo -e "${RED}✖ $script : ECHEC${NC}"
    all_passed=false
  fi
  echo -e "==============================\n"
done

if $all_passed; then
  echo -e "${GREEN}🎉 Tous les tests sont passés avec succès !${NC}"
else
  echo -e "${RED}❌ Certains tests ont échoué.${NC}"
  exit 1
fi 