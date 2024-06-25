import React from "react";
import ReactDOM from "react-dom";
import {
	AppProvider,
	Frame,
	Navigation,
	Page,
	Layout,
	Card,
	TextContainer,
	Text,
	Button,
	TopBar,
	Box,
	BlockStack,
	Grid,
	LegacyCard,
	Bleed,
	Image,
	Stack,
	FooterHelp,
	Link,
} from "@shopify/polaris";
import enTranslations from "@shopify/polaris/locales/en.json";

function Dashboard() {

	const boxStype = {
		
	  };

	return (
		<AppProvider>
			<Page>
				{/* <Card sectioned>
					<p>Please add ebay account first.</p>
					<br />
					<p>
						<Button destructive url="">
							Add Ebay Account
						</Button>
					</p>
				</Card>				 */}

				{/* <Card title="Summary" sectioned>
					<Grid>
						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 3, xl: 3 }}
						>
							<LegacyCard title="Products" sectioned>
								<p>100</p>
							</LegacyCard>
						</Grid.Cell>
						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 3, xl: 3 }}
						>
							<LegacyCard title="Published" sectioned>
								<p>200</p>
							</LegacyCard>
						</Grid.Cell>
						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 3, xl: 3 }}
						>
							<LegacyCard title="In Process" sectioned>
								<p>300</p>
							</LegacyCard>
						</Grid.Cell>
						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 3, xl: 3 }}
						>
							<LegacyCard title="Ebay Orders" sectioned>
								<p>400</p>
							</LegacyCard>
						</Grid.Cell>
					</Grid>
				</Card> */}

				<Card>
					<Grid style={{ textAlign: "center" }}>
						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 4, xl: 3 }}
							style={{
								height: "100%",
								display: "flex",
								alignItems: "center",
								justifyContent: "center",
								flexDirection: "column",
								padding: "20px",
							}}
						>
							<a href="/products">
							<LegacyCard title="" sectioned>
								<div className="dashboard-image-container" style={{'display': 'flex', 'justifyContent': 'center', 'alignItems': 'center', }}>
									<Image
										src={
											process.env.PUBLIC_URL + "/product.png"
										}
										style={{ maxWidth: "50%" }}
										alt="product"
										className="dashboard-product-image"
									/>
								</div>
								<div className="boxText">
								<p style={{								
								fontsize: '14px',
								color: '#333',
								textAlign: 'center',
								margin: '13px',
								}}>
									Manage Products Import Product By URL Bulk
									Import Incomplete Products
								</p>
								</div>
							</LegacyCard>
							</a>
						</Grid.Cell>

						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 4, xl: 3 }}
							style={{
								height: '203px',
								display: "flex",
								alignItems: "center",
								justifyContent: "center",
								flexDirection: "column",
								padding: "20px",
								paddingtop: "10px",
							}}
						>
							<a href="/amzconfig">
							<LegacyCard title="" sectioned className="custom-legacy-card">
								<div className="dashboard-image-container" style={{'display': 'flex', 'justifyContent': 'center', 'alignItems': 'center', }}>
									<Image
										src={process.env.PUBLIC_URL + "/amazon.png"}
										style={{ maxWidth: "50%" }}
										alt="Profiles"
										className="dashboard-product-image"
									/>
								</div>
								<p style={{								
								fontsize: '14px',
								color: '#333',
								textAlign: 'center',
								margin: '27px',
								}}>Link Amazon Associate Account</p>
							</LegacyCard>
							</a>
						</Grid.Cell>

						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 4, xl: 3 }}
							style={{
								height: "100%",
								display: "flex",
								alignItems: "center",
								justifyContent: "center",
								flexDirection: "column",
								padding: "20px",
							}}
						>
							<a href="/settings">
							<LegacyCard title="" sectioned>
								<div className="dashboard-image-container" style={{'display': 'flex', 'justifyContent': 'center', 'alignItems': 'center', }}>
									<Image
										src={
											process.env.PUBLIC_URL + "/setting.png"
										}
										style={{ maxWidth: "50%" }}
										alt="Profiles"
										className="dashboard-product-image"
									/>
								</div>
								<p style={{								
								fontsize: '14px',
								color: '#333',
								textAlign: 'center',
								margintop: '20px',
								margin: '13px',
								}}>
									General Settings Buy Now Link Pricing Rules
									Sync Settings
								</p>
							</LegacyCard>
							</a>
						</Grid.Cell>

						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 4, xl: 3 }}
							style={{
								height: "100%",
								display: "flex",
								alignItems: "center",
								justifyContent: "center",
								flexDirection: "column",
								padding: "20px",
							}}
						>
							<LegacyCard title="" sectioned>
								<div className="dashboard-image-container" style={{'display': 'flex', 'justifyContent': 'center', 'alignItems': 'center', }}>
									<Image
										src={
											process.env.PUBLIC_URL +
											"/knowledge.png"
										}
										style={{ maxWidth: "50%" }}
										alt="Profiles"
										className="dashboard-product-image"
									/>
								</div>								
								<p style={{								
								fontsize: '14px',
								color: '#333',
								textAlign: 'center',
								margintop: '20px',
								margin: '13px',
								}}>User Guides</p>
							</LegacyCard>
						</Grid.Cell>

						<Grid.Cell
							columnSpan={{ xs: 3, sm: 3, md: 3, lg: 4, xl: 3 }}
							style={{
								height: "100%",
								display: "flex",
								alignItems: "center",
								justifyContent: "center",
								flexDirection: "column",
								padding: "20px",
							}}
						>
							<LegacyCard title="" sectioned>
								<div className="dashboard-image-container" style={{'display': 'flex', 'justifyContent': 'center', 'alignItems': 'center', }}>
									<Image
										src={process.env.PUBLIC_URL + "/help.png"}
										style={{ maxWidth: "50%" }}
										alt="Profiles"
										className="dashboard-product-image"
									/>
								</div>								
								<p style={{								
								fontsize: '14px',
								color: '#333',
								textAlign: 'center',
								margintop: '20px',
								margin: '13px',
								}}>
									Send email at <a href="mailto:epihelp@infoshore.biz">epihelp@infoshore.biz</a> for any
									query
								</p>
							</LegacyCard>
						</Grid.Cell>
					</Grid>
				</Card>

				{/* <Card>
					<FooterHelp>
						Copyright 2022 InfoShore Technology Solutions LLP. All
						right reserved.
					</FooterHelp>
				</Card> */}
			</Page>
		</AppProvider>
	);
}

export default Dashboard;
