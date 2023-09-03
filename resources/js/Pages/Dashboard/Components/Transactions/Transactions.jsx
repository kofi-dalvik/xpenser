import Dropdown from '@/Components/Dropdown';
import { SectionTitle } from '@/Pages/Dashboard/Components';
import TransactionItem from '@/Pages/Dashboard/Components/Transactions/TransactionItem';
import AddTransaction from './AddTransaction';
import { useState } from 'react';

export default function Transactions({ auth, categories, currency, transactions, refreshDashboard }) {
    const [showAddTrans, setShowAddTrans] = useState(false);

    const items = transactions.data || [];

    return (
        <div className="bg-white fixed top-0 right-0 bottom-0 w-1/4 shadow overflow-y-auto">
            <div className="sticky top-0 bg-white shadow p-3">
                <header className="header flex justify-end p-1">
                    <div className="ml-3 relative">
                        <Dropdown>
                            <Dropdown.Trigger>
                                <span className="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium text-purple-500 bg-purple-100 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                    >
                                        <i className="mdi mdi-account-circle me-2"></i> {auth.user.name}
                                        <i className="mdi mdi-chevron-down ms-2 text-xl"></i>
                                    </button>
                                </span>
                            </Dropdown.Trigger>

                            <Dropdown.Content>
                                <Dropdown.Link href={route('profile.edit')}>
                                    <i className="mdi mdi-settings me-1"></i> Settings
                                </Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    <i className="mdi mdi-logout-variant me-1"></i> Log Out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                <SectionTitle
                    icon="mdi-history"
                    title="Transactions"
                    subtitle="Manage your categories">
                        <div className="text-end">
                            <button onClick={ () => setShowAddTrans(true) } className='btn bg-white shadow text-sm'>
                                <i className='mdi mdi-plus me-1'></i>
                                Add
                            </button>
                        </div>
                </SectionTitle>
            </div>

            <div className="p-3">
                { items.map((transaction, index) => {
                    return <TransactionItem currency={currency} transaction={transaction} key={index} />
                }) }
            </div>

            <AddTransaction
                show={ showAddTrans }
                setShow={ setShowAddTrans }
                onClose={ () => setShowAddTrans(false) }
                refreshDashboard={ refreshDashboard }
                categories={ categories }
            />
        </div>
    );
}