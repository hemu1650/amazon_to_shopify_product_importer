import { Form, FormLayout, TextField, Button, Page, Card, Select } from '@shopify/polaris';
import axios from 'axios';
import { useState, useEffect, useCallback } from 'react';
import { ToastContainer, toast } from 'react-toastify';

export default function Settings() {
  const [isLoading, setIsLoading] = useState(false);
  const [published, setPublished] = useState('');
  const [tags, setTags] = useState('');
  const [vendor, setVendor] = useState('');
  const [productType, setProductType] = useState('');
  const [inventoryPolicy, setInventoryPolicy] = useState('');
  const [defQuantity, setDefQuantity] = useState('');
  const [autoCurrencyConversion, setAutoCurrencyConversion] = useState('');
  const [token, setToken] = useState('');
  const [initialSettings, setInitialSettings] = useState({});
  const [defaultShopCurrency, setDefaultShopCurrency] = useState([]);
  const [selectedCurrency, setSelectedCurrency] = useState('');
const tempcode = localStorage.getItem('tempcode');	
  const initialValues = {
    key: tempcode,
  };

  useEffect(() => {
    const getShopifyThemeId = async () => {
      try {
        const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/authenticate`, initialValues);
        setToken(response.data.token);
      } catch (error) {
        console.error('Error fetching token:', error);
      }
    };
    getShopifyThemeId();
  }, []);

  useEffect(() => {
    const fetchSettings = async () => {
      if (token) {
        try {
          const response = await axios.get(`${process.env.REACT_APP_BASE_URL}/settings?lang=en-us`, {
            headers: {
              'Authorization': `Bearer ${token}`,
            },
          });
          const settings = response.data;
          setInitialSettings(settings);
          setPublished(settings.published || '');
          setTags(settings.tags || '');
          setVendor(settings.vendor || '');
          setProductType(settings.product_type || '');
          setInventoryPolicy(settings.inventory_policy || '');
          setDefQuantity(settings.defquantity || '');
          setAutoCurrencyConversion(settings.autoCurrencyConversion || '');
          setDefaultShopCurrency(settings.courrencies || []);
        } catch (error) {
          console.error('Error fetching settings:', error);
        }
      }
    };
    fetchSettings();
  }, [token]);


  console.log('111111',defaultShopCurrency);

  const handleSubmit = async (event) => {
    event.preventDefault();
    try {
      setIsLoading(true);
      const updatedSettings = {
        ...initialSettings,
        published,
        tags,
        vendor,
        product_type: productType,
        inventory_policy: inventoryPolicy,
        defquantity: defQuantity,
        autoCurrencyConversion,
        shopcurrency : selectedCurrency,
      };
      const response = await axios.post(
        `${process.env.REACT_APP_BASE_URL}/settings?lang=en-us`,
        updatedSettings,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );
      console.log('Response:', response.data);
      setIsLoading(false);
      toast.success("Submitted successfully");
    } catch (error) {
      setIsLoading(false);
      if (error.response && error.response.data && error.response.data.error && error.response.data.error.msg[0]) {
        toast.error(`Error: ${error.response.data.error.msg[0]}`);
      } else {
        toast.error("Something went wrong !!");
      }
      console.error('Error:', error);
    }
  };

  const handlePublishedChange = useCallback((value) => setPublished(value), []);
  const handleTagsChange = useCallback((value) => setTags(value), []);
  const handleVendorChange = useCallback((value) => setVendor(value), []);
  const handleProductTypeChange = useCallback((value) => setProductType(value), []);
  const handleInventoryPolicyChange = useCallback((value) => setInventoryPolicy(value), []);
  const handleDefQuantityChange = useCallback((value) => setDefQuantity(value), []);
  const handleAutoCurrencyConversionChange = useCallback((value) => setAutoCurrencyConversion(value), []);
  const handleDefaultShopCurrencyChange = (selectedValue) => {
    // Yahaan aap selected value ko save kar sakte hain.
    setSelectedCurrency(selectedValue);
  };

  const productPublishedOptions = [
    { label: 'Published', value: '1' },
    { label: 'Hidden', value: '0' },
  ];

  const inventoryPolicyOptions = [
    { label: "Select Inventory", value: '' },
    { label: "Shopify tracks this product's inventory", value: "shopify" },
    { label: "Don't track inventory", value: 'NO' },
  ];

  const autoCurrencyOptions = [
    { label: 'Enable', value: '1' },
    { label: 'Disable', value: '0' },
  ];



  return (
    <Page title="General Settings">
      <Card>
        <Form noValidate onSubmit={handleSubmit}>
          <FormLayout>
            <Select
              label="Products should be published?"
              options={productPublishedOptions}
              onChange={handlePublishedChange}
              value={published}
            />

            <TextField
              value={tags}
              onChange={handleTagsChange}
              label="Default Tags"
              type="text"
              autoComplete="off"
              placeholder="tshirts, mens"
            />

            <TextField
              value={vendor}
              onChange={handleVendorChange}
              label="Default Vendor"
              type="text"
              autoComplete="off"
              placeholder="Leave blank to fill form amazon"
            />

            <TextField
              value={productType}
              onChange={handleProductTypeChange}
              label="Default Product Type"
              type="text"
              autoComplete="off"
              placeholder="Leave blank to fill form amazon"
            />

            <Select
              label="Inventory Policy"
              options={inventoryPolicyOptions}
              onChange={handleInventoryPolicyChange}
              value={inventoryPolicy}
            />

            <TextField
              value={defQuantity}
              onChange={handleDefQuantityChange}
              label="Default Quantity"
              type="number"
              autoComplete="off"
              placeholder=""
            />

            {/* defaultShopCurrency */}
          
            <Select
              label="Default Shop Currency"
              options={defaultShopCurrency.map(currency => ({ label: currency.currency, value: currency.currency_code }))}
              onChange={(selectedValue) => handleDefaultShopCurrencyChange(selectedValue)}
              value={selectedCurrency} // Yahaan selectedCurrency variable ka value assign kiya hai
            />

            


            <Select
              label="Auto Currency Conversion"
              options={autoCurrencyOptions}
              onChange={handleAutoCurrencyConversionChange}
              value={autoCurrencyConversion}
            />

            <Button submit loading={isLoading}>Submit</Button>
          </FormLayout>
        </Form>
      </Card>
      <ToastContainer />
    </Page>
  );
}
