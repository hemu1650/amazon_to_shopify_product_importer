import {
	AppProvider,
	Banner,
	Button,
	DataTable,
	FooterHelp,
	Layout,
	LegacyCard,
	Link,
	Page,
	Card,
	Badge,
	ChoiceList,
	IndexFilter,
	IndexTable,
	useSetIndexFiltersMode,
	useIndexResourceState,
	IndexFilters,
	Pagination,
	Popover,
	ActionList,
	Modal,
	Frame,
	TextContainer,
	Form,
	FormLayout,
	TextField,
	Spinner,
} from "@shopify/polaris";
import axios from "axios";
import React, { useCallback, useEffect, useState } from "react";
import wrap from "word-wrap";



export default function IncompleteProducts() {
	const [isLoading, setIsLoading] = useState(false);
	const [data, setData] = useState('');
	const [token, setToken] = useState('');
	const [Products, setProducts] = useState("");
	const [Productlist, setProductlist] = useState("");
	const [activeDropdowns, setActiveDropdowns] = useState({});
	const [active, setActive] = useState(false);
const tempcode = localStorage.getItem('tempcode');
	const handleChange = useCallback(() => setActive(!active), [active]);

  	const activator = <Button onClick={handleChange}>Add Missing Field</Button>;
	
	  const handleSubmit = async () => {
		alert(1);
	  }

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

	console.log('token',token);

	useEffect(() => {
		const fetchIncompleteProducts = async () => {
		  if (token) { // Check if token is available
			try {
			  const response = await axios.get(`${process.env.REACT_APP_BASE_URL}/product/incompleteProducts?lang=en-us`, {
				headers: {
				  'Authorization': `Bearer ${token}`,
				},
			  });
			  setProducts(response);
			  setProductlist(response.data.data);
			} catch (error) {
			  console.error('Error fetching products:', error);
			}
		  }
		};
	
		fetchIncompleteProducts();
	}, [token]); // This useEffect will run whenever 'token' changes
	
	console.log('Incomplete Products',Products);

    function disambiguateLabel(key, value) {
		switch (key) {
			case "type":
				return value.map((val) => `type: ${val}`).join(", ");
			case "tone":
				return value.map((val) => `tone: ${val}`).join(", ");
			default:
				return value;
		}
	}
	function isEmpty(value) {
		if (Array.isArray(value)) {
			return value.length === 0;
		} else {
			return value === "" || value == null;
		}
	}
	const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
	const [itemStrings, setItemStrings] = useState([
		// "All",
		// "Active",
		// "Draft",
		// "Archived",
	]);
	const deleteView = (index) => {
		const newItemStrings = [...itemStrings];
		newItemStrings.splice(index, 1);
		setItemStrings(newItemStrings);
		setSelected(0);
	};
	const duplicateView = async (name) => {
		setItemStrings([...itemStrings, name]);
		setSelected(itemStrings.length);
		await sleep(1);
		return true;
	};
	const tabs = itemStrings.map((item, index) => ({
		content: item,
		index,
		onAction: () => {},
		id: `${item}-${index}`,
		isLocked: index === 0,
		actions:
			index === 0
				? []
				: [
						{
							type: "rename",
							onAction: () => {},
							onPrimaryAction: async (value) => {
								const newItemsStrings = tabs.map((item, idx) => {
									if (idx === index) {
										return value;
									}
									return item.content;
								});
								await sleep(1);
								setItemStrings(newItemsStrings);
								return true;
							},
						},
						{
							type: "duplicate",
							onPrimaryAction: async (name) => {
								await sleep(1);
								duplicateView(name);
								return true;
							},
						},
						{
							type: "edit",
						},
						{
							type: "delete",
							onPrimaryAction: async () => {
								await sleep(1);
								deleteView(index);
								return true;
							},
						},
				  ],
	}));
	const [selected, setSelected] = useState(0);
	const onCreateNewView = async (value) => {
		await sleep(500);
		setItemStrings([...itemStrings, value]);
		setSelected(itemStrings.length);
		return true;
	};
	const sortOptions = [
		{ label: "Product", value: "product asc", directionLabel: "Ascending" },
		{ label: "Product", value: "product desc", directionLabel: "Descending" },
		{ label: "Status", value: "tone asc", directionLabel: "A-Z" },
		{ label: "Status", value: "tone desc", directionLabel: "Z-A" },
		{ label: "Type", value: "type asc", directionLabel: "A-Z" },
		{ label: "Type", value: "type desc", directionLabel: "Z-A" },
		{ label: "Vendor", value: "vendor asc", directionLabel: "Ascending" },
		{ label: "Vendor", value: "vendor desc", directionLabel: "Descending" },
	];
	const [sortSelected, setSortSelected] = useState(["product asc"]);
	const { mode, setMode } = useSetIndexFiltersMode();
	const onHandleCancel = () => {};
	const onHandleSave = async () => {
		await sleep(1);
		return true;
	};
	const primaryAction =
		selected === 0
			? {
					type: "save-as",
					onAction: onCreateNewView,
					disabled: false,
					loading: false,
			  }
			: {
					type: "save",
					onAction: onHandleSave,
					disabled: false,
					loading: false,
			  };
	const [tone, setStatus] = useState(undefined);
	const [type, setType] = useState(undefined);
	const [queryValue, setQueryValue] = useState("");
	const handleStatusChange = useCallback((value) => setStatus(value), []);
	const handleTypeChange = useCallback((value) => setType(value), []);
	const handleFiltersQueryChange = useCallback(
		(value) => setQueryValue(value),
		[]
	);
	const handleStatusRemove = useCallback(() => setStatus(undefined), []);
	const handleTypeRemove = useCallback(() => setType(undefined), []);
	const handleQueryValueRemove = useCallback(() => setQueryValue(""), []);
	const handleFiltersClearAll = useCallback(() => {
		handleStatusRemove();
		handleTypeRemove();
		handleQueryValueRemove();
	}, [handleStatusRemove, handleQueryValueRemove, handleTypeRemove]);
	const filters = [
		{
			key: "tone",
			label: "Status",
			filter: (
				<ChoiceList
					title="tone"
					titleHidden
					choices={[
						{ label: "Active", value: "active" },
						{ label: "Draft", value: "draft" },
						{ label: "Archived", value: "archived" },
					]}
					selected={tone || []}
					onChange={handleStatusChange}
					allowMultiple
				/>
			),
			shortcut: true,
		},
		{
			key: "type",
			label: "Type",
			filter: (
				<ChoiceList
					title="Type"
					titleHidden
					choices={[
						{ label: "Brew Gear", value: "brew-gear" },
						{ label: "Brew Merch", value: "brew-merch" },
					]}
					selected={type || []}
					onChange={handleTypeChange}
					allowMultiple
				/>
			),
			shortcut: true,
		},
	];
	const appliedFilters = [];
	if (tone && !isEmpty(tone)) {
		const key = "tone";
		appliedFilters.push({
			key,
			label: disambiguateLabel(key, tone),
			onRemove: handleStatusRemove,
		});
	}
	if (type && !isEmpty(type)) {
		const key = "type";
		appliedFilters.push({
			key,
			label: disambiguateLabel(key, type),
			onRemove: handleTypeRemove,
		});
	}

	const shproducts = Productlist;

	console.log('shproducts',shproducts);

	const resourceName = {
		singular: "product",
		plural: "products",
	};

	// Pagination state
	const itemsPerPage = 10;
	const [currentPage, setCurrentPage] = useState(1);
	const totalPages = Math.ceil(shproducts.length / itemsPerPage);
	const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = currentPage * itemsPerPage;
	// const paginatedProducts = shproducts.slice(
	// 	(currentPage - 1) * itemsPerPage,
	// 	currentPage * itemsPerPage
	// );

	const paginate = (items, pageNumber, pageSize) => {
		const start = (pageNumber - 1) * pageSize;
		return items.slice(start, start + pageSize);
	  };
	
	const paginatedProducts = paginate(shproducts, currentPage, itemsPerPage);
	

	const { selectedResources, allResourcesSelected, handleSelectionChange } =
		useIndexResourceState(shproducts);

	const rowMarkup = Array.isArray(paginatedProducts) ? paginatedProducts.map(
		({ product_id, title, variants }, index) => {
			const wrappedTitle = wrap(title, { width: 50, indent: '' });		
			return (
				<React.Fragment key={product_id}>
					<IndexTable.Row
					id={product_id}
					selected={false} // Adjust this according to your selected resources logic
					position={index}
					>					

					{variants && Array.isArray(variants) && variants.map((variant, vIndex) => (
						<React.Fragment key={vIndex}>
						<IndexTable.Cell><img src={variant.main_image.imgurl} width="100" height="100" /></IndexTable.Cell>						
						</React.Fragment>
					))}
					
					<IndexTable.Cell>
						{wrappedTitle.split('\n').map((line, idx) => (
						<span key={idx}>{line}<br /></span>
						))}
					</IndexTable.Cell>
					
					{variants && Array.isArray(variants) && variants.map((variant, vIndex) => (
						<React.Fragment key={vIndex}>
						<IndexTable.Cell>{variant.asin}</IndexTable.Cell>
						<IndexTable.Cell>{variant.sku}</IndexTable.Cell>
						<IndexTable.Cell>{variant.price}</IndexTable.Cell>
						<IndexTable.Cell>{variant.status}</IndexTable.Cell>
						<IndexTable.Cell>      
							{activator}
						</IndexTable.Cell>
						</React.Fragment>
					))}
					</IndexTable.Row>
				</React.Fragment>
			);
		}
	) : null;

	const handlePreviousPage = () => {
		if (currentPage > 1) {
			setCurrentPage(currentPage - 1);
		}
	};

	const handleNextPage = () => {
		if (currentPage < totalPages) {
			setCurrentPage(currentPage + 1);
		}
	};

	

	return (
		<Page
			title={"Incomplete Products List"}
			
		>
			<Card padding="0">
				<IndexFilters
					sortOptions={sortOptions}
					sortSelected={sortSelected}
					queryValue={queryValue}
					queryPlaceholder="Searching in all"
					onQueryChange={handleFiltersQueryChange}
					onQueryClear={() => {}}
					onSort={setSortSelected}
					primaryAction={primaryAction}
					cancelAction={{
						onAction: onHandleCancel,
						disabled: false,
						loading: false,
					}}
					tabs={tabs}
					selected={selected}
					onSelect={setSelected}
					canCreateNewView
					onCreateNewView={onCreateNewView}
					filters={filters}
					appliedFilters={appliedFilters}
					onClearAll={handleFiltersClearAll}
					mode={mode}
					setMode={setMode}
				/>
				<IndexTable
					resourceName={resourceName}
					itemCount={shproducts.length}
					selectedItemsCount={
						allResourcesSelected ? "All" : selectedResources.length
					}
					onSelectionChange={handleSelectionChange}
					sortable={[false, true, true, true, true, true, true]}
					headings={[
						{ title: "Image" },
						{ title: "Title" },
						{ title: "ASIN" },
						{ title: "Variants" },
						{ title: "Price" },
						{ title: "Status" },
						{ title: "Actions" },
						// { title: "Price", alignment: "end" },
						// { title: "Status" },
						// { title: "Inventory" },
						// { title: "Type" },
						// { title: "Vendor" },
					]}
				>
					{rowMarkup}
				</IndexTable>
				
				<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '16px' }}>
                    <Pagination
                        hasPrevious={currentPage > 1}
                        onPrevious={handlePreviousPage}
                        hasNext={currentPage < totalPages}
                        onNext={handleNextPage}
                    />
                    <div>Showing {startIndex} to {endIndex} of {shproducts.length} entries</div>
                </div>
				
				{/* <div style={{height: '500px'}}>
				<Frame>
					<Modal
					activator={activator}
					open={active}
					onClose={handleChange}
					title="Add Price"					
					>
					<Modal.Section>
						<TextContainer>
						<Form noValidate onSubmit={handleSubmit}>
							<FormLayout>
								<TextField
								
								label="Price"
								type="url"
								autoComplete="off"
								/>   
								{isLoading ? (
									<Spinner accessibilityLabel="Spinner example" size="large" />
								) : (
									<Button submit>Submit</Button>
								)}
							</FormLayout>
							</Form>
						</TextContainer>
					</Modal.Section>
					</Modal>
				</Frame>
				</div> */}
			</Card>
		</Page>
	);
}
