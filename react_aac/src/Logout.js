import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

const Logout = ({ setToken }) => {
  const navigate = useNavigate();
    useEffect(() => {
      localStorage.removeItem('tempcode');
      localStorage.removeItem('shopurl');
    // setToken(null);  // Clear the token
        navigate('/');   // Redirect to the login page
        window.location.reload();
  }, [setToken, navigate]);

  return null;  // This component doesn't render anything
};

export default Logout;
