import { Form, FormLayout, TextField, Button, Page, Card, Select, Text } from '@shopify/polaris';
import axios from 'axios';
import { useState, useCallback, useEffect } from 'react';
import { ToastContainer, toast } from 'react-toastify';

export default function BuyNowLink() {
  const [isLoading, setIsLoading] = useState(false);
  const [data, setData] = useState('');
  const [token, setToken] = useState('');
  const [buynowtext, setbuynowtext] = useState('');
  const [themeSelectedValue, setThemeSelectedValue] = useState('');
  const [buynowlink, setbuynowlink] = useState('0'); // State to manage the selected buynowlink
  const [shopInstallationInstructions, setshopInstallationInstructions] = useState(false); // State to manage visibility
  const [otherThemeText, setOtherThemeText] = useState(''); // State for other theme text
  const homeUrl = window.location.origin;
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
  }, []); // Empty dependency array means this useEffect runs only once

  const handleSubmit = async (event) => {
    event.preventDefault();
    try {
      setIsLoading(true);
      const response = await axios.post(
        `${process.env.REACT_APP_BASE_URL}/settings/buynow?lang=en-us`,
        {
          buynow: buynowlink,
          selectedTheme: themeSelectedValue,
          buynowtext: buynowtext,
          otherThemeText: themeSelectedValue === 'other' ? otherThemeText : '', // Include otherThemeText if "Other" is selected
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
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

  const handlebuynowtextChange = useCallback((value) => setbuynowtext(value), []);
  const handleSelectThemeChange = useCallback((value) => setThemeSelectedValue(value), []);
  const handleOtherThemeTextChange = useCallback((value) => setOtherThemeText(value), []);

  const handlebuynowlinkChange = (value) => {
    setbuynowlink(value);
    if (value === '1') { // Show the field when 'Enable' is selected
      setshopInstallationInstructions(true);
    } else {
      setshopInstallationInstructions(false);
    }
  };

  // Options for the buynowlink select dropdown
  const amazonbuynowlink = [
    { label: 'Redirect users from Product page', value: '1' },
    { label: "Don't redirect users to Amazon", value: '0' },
  ];
  const selectTheme = [
    { label: 'Dawn', value: 'dawn' },
    { label: 'Craft', value: 'craft' },
    { label: 'Sense', value: 'sense' },
    { label: 'Refresh', value: 'refresh' },
    { label: 'Other' , value: 'other' },
  ];

  const areFieldsDisabled = buynowlink === '0';

  return (
    <>
      <Page title='Buy Now Link'>
        <Card>
          <Form noValidate onSubmit={handleSubmit}>
            <FormLayout>
              <Select
                label="Add Amazon Buy Now Link"
                options={amazonbuynowlink}
                onChange={handlebuynowlinkChange}
                value={buynowlink}
              />

              <Select
                label="Select a Theme"
                options={selectTheme}
                onChange={handleSelectThemeChange}
                value={themeSelectedValue}
                disabled={areFieldsDisabled}
              />

              {themeSelectedValue === 'other' && !areFieldsDisabled && (
                <TextField
                  value={otherThemeText}
                  onChange={handleOtherThemeTextChange}
                  label="Other Theme Name"
                  type="text"
                  autoComplete="off"
                />
              )}

              <TextField
                value={buynowtext}
                onChange={handlebuynowtextChange}
                label="Buy Now Text"
                type="text"
                autoComplete="off"
                disabled={areFieldsDisabled}
              />
              <Button submit loading={isLoading}>Submit</Button>
            </FormLayout>
          </Form>
          <br></br>
          <div className="note">
            <p>Please go through this link <a href='https://infoshoreapps.zendesk.com/hc/en-us/articles/360017711113-How-to-configure-Buy-Now-Link-Settings'>Click here</a> in which you can add View On Amazon button manually</p>
          </div>
        </Card>
        <ToastContainer />
      </Page>

      {shopInstallationInstructions && (
        <Page>
          <Card>
            <div className='installinstruction'>
            <p>1. Login to Shopify Admin and click on Themes under Online Store.</p>
            <br></br>
            <img src={process.env.PUBLIC_URL + '/usrguide/userguide1.png'} alt="User Guide" />
            <br></br>
            <p>2. Click on Edit Code under Action menu.</p>
            <br></br>
            <img src={process.env.PUBLIC_URL + '/usrguide/userguide2.png'} alt="User Guide" />
            <br></br>
            <p>3. Under Layout find the file theme.liquid</p>
            <br></br>
            <img src={process.env.PUBLIC_URL + '/usrguide/userguide3.png'} alt="User Guide" />
            <br></br>
            <p>4. Copy the below code just before closing of as shown in the below screenshot.</p>
            <br></br>
            <img src={process.env.PUBLIC_URL + '/usrguide/userguide4.png'} alt="User Guide" />
            <br></br>
            <p>Please contact at shopifyapps@infoshore.biz if you need any assistance.</p>
            <br></br>
            </div>
          </Card>
        </Page>
      )}
    </>
  );
}
