import { Form, FormLayout, Button, Page, Card, Select } from '@shopify/polaris';
import axios from 'axios';
import { useState, useEffect } from 'react';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

export default function AutoSync() {
  const [isLoading, setIsLoading] = useState(false);
  const [inventory_sync, setInventorySync] = useState('2');
  const [price_sync, setPriceSync] = useState('2');
  const [outofstock_action, setOutOfStockAction] = useState('');
  const [token, setToken] = useState('');
  const [showOutOfStockAction, setShowOutOfStockAction] = useState(false); // State to manage visibility
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

  const handleSubmit = async (event) => {
    event.preventDefault();
    try {
      setIsLoading(true);
      const response = await axios.post(
        `${process.env.REACT_APP_BASE_URL}/settings/sync?lang=en-us`,
        {
          inventory_sync,
          price_sync,
          outofstock_action,
        },
        {
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        }
      );
      console.log('Response:', response.data);
      setIsLoading(false);
      toast.success("Submit successfully");
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

  const handleInventorySyncChange = (value) => {
    setInventorySync(value);
    if (value === '1') { // Show the field when 'Enable' is selected
      setShowOutOfStockAction(true);
    } else {
      setShowOutOfStockAction(false);
    }
  };

  const inventorySyncOptions = [
    { label: 'Enable', value: '1' },
    { label: 'Disable', value: '2' },
  ];

  const priceSyncOptions = [
    { label: 'Enable', value: '1' },
    { label: 'Disable', value: '2' },
  ];

  const outOfStockOptions = [
    { label: 'Mark it as Sold Out (Quantity = 0) on Shopify', value: 'outofstock' },
    { label: 'Unpublish out-of-stock products', value: 'unpublish' },
    { label: 'Delete out-of-stock products', value: 'delete' },
  ];

  return (
    <Page title='Auto Sync'>
      <Card>
        <Form noValidate onSubmit={handleSubmit}>
          
          <FormLayout>
            <div style={{ color: 'green' }}>
              <p>These Settings Work In Auto Sync Which Runs Every 24 Hours.</p>
            </div>

            <Select
              label="Inventory Synchronization"
              options={inventorySyncOptions}
              onChange={handleInventorySyncChange}
              value={inventory_sync}
            />

            {showOutOfStockAction && (
              <Select
                label="Out-Of-Stock Action"
                options={outOfStockOptions}
                onChange={setOutOfStockAction}
                value={outofstock_action}
              />
            )}

            <Select
              label="Price Synchronization"
              options={priceSyncOptions}
              onChange={setPriceSync}
              value={price_sync}
            />

            <div style={{ display: 'flex', gap: '8px' }}>
              <Button submit loading={isLoading} primary>Submit</Button>
            </div>
          </FormLayout>
        </Form>
      </Card>
      <ToastContainer />
    </Page>
  );
}
