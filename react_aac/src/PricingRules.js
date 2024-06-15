import {Form, FormLayout, TextField, Button, Page, Card, Text, Select} from '@shopify/polaris';
import axios from 'axios';
import {useState, useCallback, useEffect} from 'react';
import { ToastContainer, toast } from 'react-toastify';

export default function PricingRules() {
  const [price_sync, setprice_sync] = useState('');
  const [markuptype, setMarkuptype] = useState('');
  const [markupval, setMarkupval] = useState('');
  const [markupround, setmarkupround] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [token, setToken] = useState('');
  const [initialSettings, setInitialSettings] = useState({});
  const tempcode = localStorage.getItem('tempcode');
  const price_syncOptions = [
    {label: 'Enable', value: '1'},
    {label: 'Disable', value: '0'},
  ];

  const markupType = [
    {label: 'Fixed Amount', value: 'fixed'},
    {label: 'Percentage', value: 'percent'},
  ];

  const markuproundoption = [
    {label: 'Round off price to nearest 0.99', value: '1'},
    {label: 'Do not round off price', value: '0'},
  ];

  const handleprice_syncChange = useCallback((value) => setprice_sync(value), []);
  const handleMarkupTypeChange = useCallback((value) => setMarkuptype(value), []);
  const handlemarkupvalChange = useCallback((value) => setMarkupval(value), []);
  const handlemarkuproundChange = useCallback((value) => setmarkupround(value), []);

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
          setprice_sync(settings.price_sync);
          setMarkuptype(settings.markuptype);
          setMarkupval(settings.markupval);
          setmarkupround(settings.markupround);
        } catch (error) {
          console.error('Error fetching settings:', error);
        }
      }
    };
    fetchSettings();
  }, [token]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    try {
      setIsLoading(true);
      const updatedSettings = {
          ...initialSettings,
        markupenabled: price_sync,
        markuptype: markuptype,
        markupval: markupval,
        markupround: markupround,
      };
      console.log('Submitting settings:', updatedSettings); // Log the payload
      const response = await axios.post(
        `${process.env.REACT_APP_BASE_URL}/settings/pricingrules?lang=en-us`,
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
      console.error('Error:', error); // Log the error
      if (error.response && error.response.data && error.response.data.error && error.response.data.error.msg[0]) {
        toast.error(`Error: ${error.response.data.error.msg[0]}`);
      } else {
        toast.error("Something went wrong !!");
      }
    }
  };

  return (
    <Page title='Pricing Rules'>
        <Card>
            <Form noValidate onSubmit={handleSubmit}>
            <FormLayout>
                <Select
                label="Enable Price Markup"
                options={price_syncOptions}
                onChange={handleprice_syncChange}
                value={price_sync}
                />
                <Select
                label="Markup Type"
                options={markupType}
                onChange={handleMarkupTypeChange}
                value={markuptype}
                />
                <TextField
                value={markupval}
                onChange={handlemarkupvalChange}
                label="Markup Value"
                type="number"
                autoComplete="off"
                />
                <Select
                label="Markup Round Off"
                options={markuproundoption}
                onChange={handlemarkuproundChange}
                value={markupround}
                />
                <div style={{ display: 'flex', gap: '8px' }}>
                <Button submit loading={isLoading} primary className="submit-button">Submit</Button>
                </div>
            </FormLayout>
            </Form>
        </Card>
        <ToastContainer />
    </Page>
  );
}
