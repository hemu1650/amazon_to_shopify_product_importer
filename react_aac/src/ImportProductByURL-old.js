import { Form, FormLayout, TextField, Button, Page, Card, Link, Spinner, LegacyCard, Tabs } from '@shopify/polaris';
import axios from 'axios';
import { useState, useCallback, useEffect } from 'react';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

export default function ImportProductByURL() {

  const [data, setData] = useState('');
  const [token, setToken] = useState('');
  const [producturl, setProductUrl] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [selected, setSelected] = useState(0);
  const [urlError, setUrlError] = useState('');
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
      } catch (error) {
        console.error('Error fetching token:', error);
      }
    };

    getShopifyThemeId();
  }, []);

  const handleUrlChange = useCallback((value) => {
    setProductUrl(value);
    // Resetting the error when user changes the URL
    setUrlError('');
  }, []);

  const handleSubmit = async () => {
    try {
      setIsLoading(true);
      const response = await axios.post(
        `${process.env.REACT_APP_BASE_URL}/product/add?lang=en-us`,
        { producturl },
        {
          headers: {
            'Authorization': `Bearer ${token}`,
          }
        }
      );
      setIsLoading(false);
      toast.success("Product added successfully");
      setProductUrl('');
    } catch (error) {
      setIsLoading(false);
      if (error.response.data.error.msg[0]) {
        toast.error(`Error: ${error.response.data.error.msg[0]}`);
      } else {
        toast.error("Something went wrong !!");
      }
      console.error('Error:', error);
    }
  };

  const handleValidation = () => {
    if (!producturl) {
      setUrlError('URL is required');
      return false;
    }
    // Add additional validation logic here if needed
    return true;
  };

  const renderTabContent = () => {
    switch (selected) {
      case 0:
        return (
          <LegacyCard.Section title="Single Variant">

            <Form onSubmit={(event) => {
              event.preventDefault();
              if (handleValidation()) {
                handleSubmit();
              }
            }}>
              <FormLayout>
                <TextField
                  value={producturl}
                  onChange={handleUrlChange}
                  label="Please enter Amazon product URL"
                  type="url"
                  autoComplete="off"
                  error={urlError}
                />
                {isLoading ? (
                  <Spinner accessibilityLabel="Spinner example" size="large" />
                ) : (
                    <Button submit>Submit</Button>
                  )}
              </FormLayout>
            </Form>
          </LegacyCard.Section>
        );
      default:
        return null;
    }
  };

  return (
    <div className='importproductbyurl'>
      <Page title={"Import Product By URL"}
        secondaryActions={[
          {
            content: (
              <Link class="helplink" style={{ 'backgrounColor': '#1c2260' }} target="_blank" to="https://infoshoreapps.zendesk.com/hc/en-us/articles/360016229474-How-to-import-product-by-URL-to-your-Shopify-store">
                Need Help?
              </Link>
            ),
          },
        ]}
      >
        <ToastContainer />
        <Card>
          {renderTabContent()}
        </Card>
      </Page>
    </div>
  );
}
