import {Form, FormLayout, TextField, Button, Page, Card, Select} from '@shopify/polaris';
import axios from 'axios';
import {useState, useCallback, useEffect} from 'react';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

export default function AmazonAccount() {
  const [isLoading, setIsLoading] = useState(false);
  const [data, setData] = useState('');
  const [token, setToken] = useState('');
  const [AmazonAssociateTag, setAmazonAssociateTag] = useState('');
  const [region, setRegion] = useState('com'); // State to manage the selected region
  const [associateIdError, setAssociateIdError] = useState(''); // State for validation error
  const tempcode = localStorage.getItem('tempcode');	
  const initialValues = {
    key: tempcode,
  };

  useEffect(() => {
    const getShopifyThemeId = async () => {
      try {
        const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/authenticate`, initialValues);
        setData(response.data);
        setToken(response.data.token);
        // console.log('response-token', response.data.token);
      } catch (error) {
        console.error('Error fetching token:', error);
      }
    };
    getShopifyThemeId();
  }, []); // Empty dependency array means this useEffect runs only once

  const handleSubmit = async (event) => {
    event.preventDefault();
    // Validation: check if associateId is empty
    if (!AmazonAssociateTag.trim()) {
      setAssociateIdError('This field is required');
      return;
    } else {
      setAssociateIdError('');
    }
    try {
      setIsLoading(true);
      const response = await axios.post(
        `${process.env.REACT_APP_BASE_URL}/amzconfig?lang=en-us`,
        {
          associate_id: AmazonAssociateTag,
          country: region,
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

  const handleAmazonAssociateTag = useCallback((value) => setAmazonAssociateTag(value), []);

  const handleRegionChange = useCallback((value) => setRegion(value), []);

  // Options for the region select dropdown
  const regionOptions = [
    {label: 'Amazon.com', value: 'com'},
    {label: 'Amazon.ca', value: 'ca'},
    {label: 'Amazon.co.uk', value: 'co.uk'},
    {label: 'Amazon.in', value: 'in'},
    {label: 'Amazon.com.br', value: 'com.br'},
    {label: 'Amazon.com.mx', value: 'com.mx'},
    {label: 'Amazon.de', value: 'de'},
    {label: 'Amazon.es', value: 'es'},
    {label: 'Amazon.fr', value: 'fr'},
    {label: 'Amazon.co.jp', value: 'co.jp'},
    {label: 'Amazon.cn', value: 'cn'},
  ];

  return (
    <Page title="Amazon Account">
      <Card>
        <p className="note" style={{ 'color': "red" }}>NOTE: You do not require Amazon AWS keys to setup your account</p>
        <br></br>
        <Form noValidate onSubmit={handleSubmit}>
          <FormLayout>
            <TextField
              value={AmazonAssociateTag}
              onChange={handleAmazonAssociateTag}
              label="Your Amazon Associate Tag*"
              type="url"
              autoComplete="off"
              error={associateIdError} // Display error message
            />
            <Select
              label="Marketplace"
              options={regionOptions}
              onChange={handleRegionChange}
              value={region}
            />
            <p style={{'color': 'maroon'}}>Please note that the APP will import the product prices in the base currency of your chosen amazon marketplace. So update your Shopify store currency to match with chosen amazon marketplace.</p>
            <Button submit disabled={isLoading}>Submit</Button>
          </FormLayout>
        </Form>
      </Card>
      <ToastContainer />
    </Page>
  );
}
