import React, { useState, useEffect } from 'react';
import './Styles.css';
import "@shopify/polaris/build/esm/styles.css";
import enTranslations from '@shopify/polaris/locales/en.json';
import { BrowserRouter as Router, Route, Navigate, Routes } from 'react-router-dom';
import {
  AppProvider,
  Frame,
  TopBar,
} from '@shopify/polaris';
import AppNavigation from "./Navigation";
import { PasswordProvider } from './PasswordContext';
import Dashboard from "./Dashboard";
import ManageProducts from './ManageProducts';
import ImportProductByURL from './ImportProductByURL';
import BulkImport from './BulkImport';
import IncompleteProducts from './IncompleteProducts';
import AmazonAccount from './AmazonAccount';
import Settings from './Settings';
import BuyNowLink from './BuyNowLink';
import PricingRules from './PricingRules';
import AutoSync from './AutoSync';
import Plans from './Plans';
import Loginform from './Loginform';
import axios from 'axios';
import Logout from './Logout';
import Review from './Review';
import ReviewEdit from './ReviewEdit';

function App() {
  const [logo_url, setLogourl] = useState(process.env.PUBLIC_URL + '/logo1.png');
  const [app_url, setAppurl] = useState('http://localhost:3000/');
  const [app_name, setAppname] = useState("eBay Exporter by Infoshore");
  const [token, setToken] = useState(localStorage.getItem('tempcode')); // Retrieve token from localStorage

  const [isSecondaryMenuOpen, setIsSecondaryMenuOpen] = useState(false);
  const [isSearchActive, setIsSearchActive] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const shopurl = localStorage.getItem('shopurl');
  
  useEffect(() => {
    const storedToken = localStorage.getItem('shopurl');
    if (storedToken) {
      setToken(storedToken);
    }
  }, []);


  const logo = {
    topBarSource: logo_url,
    width: 250,
    url: app_url,
    accessibilityLabel: 'Amazon Associate Connector',
  };

  const userMenuMarkup = (
    <TopBar.UserMenu
      name={shopurl}
    />
  );

  const topBarMarkup = (
    <TopBar
      showNavigationToggle
      userMenu={userMenuMarkup}
      searchResultsVisible={isSearchActive}
      style={{ backgroundColor: 'blue' }}
    />
  );

  const appRoutes = (
    <Routes>
      <Route path="/" element={token ? <Navigate to="/dashboard" /> : <Loginform setToken={setToken} />} />
      <Route path="/dashboard" element={token ? <Dashboard /> : <Navigate to="/" />} />
      <Route path="/products" element={token ? <ManageProducts /> : <Navigate to="/" />} />
      <Route path="/ImportProductByURL" element={token ? <ImportProductByURL /> : <Navigate to="/" />} />
      <Route path="/BulkImport" element={token ? <BulkImport /> : <Navigate to="/" />} />
      <Route path="/IncompleteProducts" element={token ? <IncompleteProducts /> : <Navigate to="/" />} />
      <Route path="/amzconfig" element={token ? <AmazonAccount /> : <Navigate to="/" />} />
      <Route path="/settings" element={token ? <Settings /> : <Navigate to="/" />} />
      <Route path="/BuyNowLink" element={token ? <BuyNowLink /> : <Navigate to="/" />} />
      <Route path="/PricingRules" element={token ? <PricingRules /> : <Navigate to="/" />} />
      <Route path="/AutoSync" element={token ? <AutoSync /> : <Navigate to="/" />} />
      <Route path="/plans" element={token ? <Plans /> : <Navigate to="/" />} />
      <Route path="https://infoshoreapps.zendesk.com/hc/en-us/categories/360001364874-Amazon-Associate-Connector" />
      <Route path="/logout" element={<Logout setToken={setToken} />} />
      <Route path="/review" element={token ? <Review /> : <Navigate to="/" />} />
      <Route path="/review-edit/:id" element={token ? <ReviewEdit /> : <Navigate to="/" />} />
    </Routes>
  );

  return (
    <AppProvider i18n={enTranslations}>
      <Router>
        {token
          ? <Frame
            topBar={topBarMarkup}
            navigation={<AppNavigation />}
            logo={logo}
          >
            {appRoutes}
          </Frame>
          : <Frame
          >
            <div className="page-container">
              <div className="">
                {appRoutes}
              </div>
            </div>
          </Frame>
        }
      </Router>
    </AppProvider>
  );
}

export default App;
