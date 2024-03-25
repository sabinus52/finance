/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import "./styles/app.css";
import "olix-backoffice/olixbo.min.css";

import "./bootstrap.js";
import "olix-backoffice";

import "./scripts/transaction.js";
import "./scripts/reconciliation.js";
import "./scripts/project.js";

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
