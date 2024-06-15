// Navigation.js

import React, { useState } from 'react';
import { Navigation } from '@shopify/polaris';

function AppNavigation() {
  const [base_url, setBaseurl] = useState('');
  const location = typeof window !== 'undefined' ? window.location.href : '';

  // State to manage open/close of submenus
  const [isProductsOpen, setIsProductsOpen] = useState(false);
  const [isSettingsOpen, setIsSettingsOpen] = useState(false);

  // Toggle functions
  const handleProductsClick = (event) => {
    setIsProductsOpen(!isProductsOpen);
  };

  const handleSettingsClick = (event) => {
    setIsSettingsOpen(!isSettingsOpen);
  };

  return (
    <Navigation location={location}>
      <Navigation.Section
        items={[
          {
            label: 'Dashboard',
            url: base_url + '/dashboard',
            selected: location === base_url + '/dashboard',
          },
          {
            label: 'Products',
            url: '#', // Dummy URL to prevent navigation
            onClick: handleProductsClick, // Use onClick to toggle
            selected: isProductsOpen, // Use state to manage open/close
            subNavigationItems: isProductsOpen ? [
              {
                label: 'Manage Products',
                url: base_url + '/products',
              },
              {
                label: 'Import Product By URL',
                url: base_url + '/ImportProductByURL',
              },
              {
                label: 'Bulk Import',
                url: base_url + '/BulkImport',
              },
              {
                label: 'Incomplete Products',
                url: base_url + '/IncompleteProducts',
              },
            ] : [],
          },
          {
            label: 'Amazon Account',
            url: base_url + '/amzconfig',
            selected: location.includes(base_url + '/amzconfig'),
          },
          {
            label: 'Settings',
            url: '#', // Dummy URL to prevent navigation
            onClick: handleSettingsClick, // Use onClick to toggle
            selected: isSettingsOpen, // Use state to manage open/close
            subNavigationItems: isSettingsOpen ? [
              {
                label: 'General Settings',
                url: base_url + '/settings',
              },
              {
                label: 'Buy Now Link',
                url: base_url + '/BuyNowLink',
              },
              {
                label: 'Pricing Rules',
                url: base_url + '/PricingRules',
              },
              {
                label: 'Auto Sync',
                url: base_url + '/AutoSync',
              },
            ] : [],
          },
          {
            label: 'Plan & Pricing',
            url: base_url + '/plans',
          },
          {
            label: 'User Guides',
            url: 'https://infoshoreapps.zendesk.com/hc/en-us/categories/360001364874-Amazon-Associate-Connector',
          },
          {
            label: 'Logout',
            url: '/logout',
          },
        ]}
      />
    </Navigation>
  );
}

export default AppNavigation;
